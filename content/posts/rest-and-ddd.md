---
title: REST and DDD
date: 2016-03-30T12:00:00-05:00
---

I recently gave a talk at the Laravel SF meetup about hexagonal architecture in PHP, and demonstrated it using Laravel.  Here's the slide deck I made:

<iframe src="https://docs.google.com/presentation/d/1kqcMqqmZbttgJcBCvmpXbzlTHVphcRCioshpx_bjc1w/embed?start=false&loop=false&delayms=3000" frameborder="0" width="480" height="299" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true"></iframe>
<br/>

While living in California I worked on a REST API that took a lot of ideas from Domain Driven Design (DDD) and Hexagonal Architecture.  The talk was mostly an introduction to Hexagonal architecture, but it also covers some valuable lessons I learned about combining DDD and REST.

## Quicksilver

I put together a demo application to explain what I was talking about.  Since the big idea of hexagonal architecture is that your application on the inside is completely isolated from the outside (HTTP and databases basically), I wrote the application as a separate repository that got dropped into the framework.  The core is [in this repository](https://github.com/yuloh/quicksilver), and I dropped it into Laravel [in this repository](https://github.com/yuloh/l5-quicksilver).

So the domain has some entities like [this delivery](https://github.com/yuloh/quicksilver/blob/master/src/Domain/Delivery.php) which are just plain old PHP objects.  The delivery has these fields:

```php
<?php
class Delivery
{
    private $id;
    private $pickup;
    private $destination;
    private $requester;
    private $priority;
    private $signature;
    private $status;
}
```

Since a delivery can be picked up by a courier, there is a [pickup request](https://github.com/yuloh/quicksilver/blob/master/src/Application/Delivery/DeliverRequest.php) for all of the info you need to pickup a delivery and a [pickup handler](https://github.com/yuloh/quicksilver/blob/master/src/Application/Delivery/Deliver.php) that accepts the request.  It basically looks like this:

```php
<?php
class DeliverRequest
{
    public function __construct($deliveryId, $signature)
    {
        $this->deliveryId = $deliveryId;
        $this->signature  = $signature;
    }
}
```

```php
<?php
class Deliver
{
    public function __construct(Delivery\Repository $deliveryRepository)
    {
        $this->deliveryRepository = $deliveryRepository;
    }

    public function __invoke(DeliverRequest $request)
    {
        $delivery = $this->deliveryRepository->find($request->getDeliveryId());
        if (!$delivery) {
            throw new EntityNotFoundException();
        }
        $delivery->deliver($request->getSignature());
        $this->deliveryRepository->save($delivery);
        return $delivery;
    }
}
```

It's a straightforward action and the data needed for it.  Splitting it up into two separate classes has a couple of benefits, but they aren't important for this discussion.  All of this logic is in the core application, since it's integral to what the fictional courier company app does.  The logic will be the same regardless of being exposed through a REST API or a 1990's HTML form.

## Mapping REST Resources On To Domain Entities

If you were writing a REST api for this app you would have a delivery resource.  It would probably look like this:

```
GET http://api.quicksilver.dev/deliveries/29292
```

```json
{
    "id": 29292,
    "pickup_name": "Tyler Durden",
    "pickup_street": "555 Paper St",
    "pickup_city": "chicago",
    "pickup_state": "IL",
    "pickup_post_code": "66043",
    "dropoff_name": "Joey Ramone",
    "dropoff_street": "222 Michigan Ave",
    "dropoff_city": "chicago",
    "dropoff_state": "IL",
    "dropoff_post_code": "99922",
    "priority": "STANDARD",
    "signature": null
}
```

Now the question is, how do you map the delivery resource to the delivery entity?  Since they both have a bunch of fields with data, it seems logical to serialize the entity as JSON for GET requests, and call the setters for each field on PATCH requests.

```php
<?php

class DeliveryController
{
    public function find($id)
    {
        $delivery = $this->deliveryRepository->get($id);

        return json_encode($delivery);
    }

    public function update($id)
    {
        $delivery = $this->deliveryRepository->find($id);

        $delivery->updateFromArray($request->json());

        $this->deliveryRepository->save($delivery);

        return json_encode($delivery);
    }
}
```

> You should be mapping the REST resource to domain actions, not entities.

This works, but taking this approach with DDD means you are wasting your time.  There isn't really any point to having a rich domain with services and objects with behaviour if you are just going to treat your objects as bags of getters and setters.

Instead, think about mapping resources to domain actions, not entities. A PATCH request usually has a couple of different intentions.  `PATCH /users/12` with the payload `{ "phone_number" : "727-555-1234"}` could coorespond to the action `UpdateContactInfo`, which `PATCH /users/12` with the payload `{ "password": "newPassw0rd"}` would coorespond to the action `UpdatePassword`.  Think about what these intentions are and focus on mapping those.  Once you have figured out what actions the resource has, you know what fields you need to expose for GET requests.

For our delivery example, a PATCH request means the API user either wants to pickup or dropoff a delivery.  The update method of the [delivery controller](https://github.com/yuloh/l5-quicksilver/blob/master/app/Http/Controllers/DeliveryController.php) ended up looking like this:

```php
<?php
class DeliveryController
{
    public function update(Delivery\Pickup $pickup, Delivery\Deliver $deliver, Request $httpRequest, $deliveryId)
    {
        if ($httpRequest->input('status') === Status::PICKED_UP) {
            $request = $this->marshal(
                Delivery\PickupRequest::class,
                $httpRequest,
                compact('deliveryId')
            );
            $delivery = $pickup($request);
        } else if ($httpRequest->input('status') === Status::DELIVERED) {
            $request = $this->marshal(
                Delivery\DeliverRequest::class,
                $httpRequest,
                compact('deliveryId')
            );
            $delivery = $deliver($request);
        } else {
            throw new HttpResponseException(response('Unknown Request', 400));
        }

        return fractal()
            ->item($delivery)
            ->transformWith(new DeliveryTransformer())
            ->toArray();
    }
}
```

By building our REST resource around actions, our internal domain entity is free to change without affecting the REST resource.  You get to model your domain in user stories (like "I want to pickup a delivery") and you only have to do a little bit of work to map HTTP requests to actions.

## Complex Resources

You might end up with a lot of different actions on one resource.  Usually this is a sign that you have another resource waiting to be extracted.  Using the delivery example, it might be worth splitting it up into two separate resources, delivery and deliver-status.

```
GET /deliveries
GET /delivery-statuses
```

Then all of the logic for updating the status is moved into the `DeliveryStatusController`, and your DeliveryController can focus on stuff like updating the address.  Since status changes usually have extra metadata you need to save with the status change (like who paid for an order and the payment method), it's almost always a good idea to break out status changes into a separate resource.

## Staying Sane

Doing DDD with a REST API starts to feel like you are spending all day mapping your REST resource on to your domain model on to your database.  It can get pretty tiresome.  I would think long and hard about building an entire domain layer if the app would really work fine with some active record models and mass assignment.  Or maybe [get rid of the backend entirely](http://nobackend.org/)  and just use Firebase or CouchDB?
