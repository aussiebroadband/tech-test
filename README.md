# Overview

Welcome to the AussieBroadband Software Engineering Laravel Tech Test.

This repository contains a Laravel 9 installation and requires php@8.1 and composer installed.

Start by cloning/forking this repo, copy `.env.example` to `.env` and run `composer install` to get started.

An in memory sqlite database is already configured for testing.

Should you wish to spin up an environment to suit your workflow during development, you are completely free to do so, however keep in mind that solutions will be evaluated on the tests you write, no additional setup should be required for `php artisan test` to be executed.

## The Tasks

We are mindful of your time and the tasks below are overly simplified to accommodate various valid solutions. Your solutions will be evaluated on your use of tests as well as your implementation and understanding of Laravel conventions, you do not need to "impress" us with everthing you know or coding "clever" solutions. Work with the structure provided and feel free to get in touch if there are any gaps.

If you have any questions before you start any of these tasks, please email jarrod.tomholt@team.aussiebroadband.com.au.

### 1. New Order Confirmation email

Applications are the main entry point for all new service orders in our systems and as such when a new application is created we want to send a `New Order Confirmation` email to the customer.

Content for the email will be determined at a later date and as such placeholder content is sufficient.

No email should actually be sent as part of this task, and you must verify that the following details are available in the email:
- Customers full name
- Monthly cost and name of the chosen plan

It is important to note, the request lifecyle should not be impacted by sending this email.

### Task 2 - Expose a new api endpoint to list all applications

We need to expose a new internal api endpoint to list all applications in the system and should accept an optional plan type filter `(null, nbn, opticomm, mobile)` for user experience. This endpoint will exist only for authenticated users and will be consumed by an SPA frontend.

As part of exposing these details we only want to provide the following data:
- Application id
- Customer full name
- Address
- Plan type
- Plan name
- State
- Plan monthly cost
- Order Id (only for orders in the `order` queue)

The following must also be observed
- The data returned should be paginated for scalability.
- The oldest applications must be at the top of the list.
- Plan monthly cost is stored as cents in the database, this must be displayed in human readable dollar format.

***NOTE:*** You are not required to implement any additional auth features/tests, and you can assume any/all auth assocaited tests are already done. You are also not required to build out the fontend as part of this task.

### Task 3 - Automate the ordering of all nbn applications

Once received and all internal business rules have been satisfied (out of scope for this task), an application will move to an `order` queue. From this queue applications can be ordered via the appropriate B2B integration for the plan type and if successful will continue through the processes.

For this task, you will be required identify and process any `nbn` application in the order queue with the following business logic
- a. Must pick up and process `nbn` applications every 5 minutes.
- b. Each application must be processed on a queue (assume queue worker is configured).
- c. Must store the Order ID on the application and progress to `complete` queue if successful.
- d. Progress to `order failed` queue in the event of a failed order or error.

You are required to send a `Http::post` request to the B2B endpoint (an `env('NBN_B2B_ENDPOINT')` variable exists for this purpose) with the following application and plan details:
- address_1
- address_2
- city
- state
- postcode
- plan name

***NOTE:*** You should not send any actual http requests as part of this task, a sample successful and failure response can be found in `test\stubs\nbn-successful-response.json` and `test\stubs\nbn-fail-response.json`.

## Submissions

Your solutions can be submitted in 2 ways: either a public PR to this repo or by emailing a zip/tarball (without the vendor dir and preserving git history) to jarrod.tomholt@team.aussiebroadband.com.au.

You should not require any additional packages to complete these tasks, if you do decide to add additional packages please specify your reasoning to do so in your PR/email submission.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
