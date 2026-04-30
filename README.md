# Overview

Welcome to the AussieBroadband Software Engineering Laravel Tech Test.

This repository is a Laravel 12 application targeting PHP 8.2+. The recommended way to run it is via Docker Compose — you do not need PHP or Composer installed locally.

## Quick start

```
docker compose up
```

That command builds the image on first run, installs Composer dependencies, creates `.env`, generates `APP_KEY`, runs migrations, and seeds a default user. The app is then served at <http://localhost:8000>.

Default test user: `test@example.com` / `password`.

### Running things

```
docker compose exec app php artisan test          # run the test suite
docker compose exec app vendor/bin/pint           # apply code style
docker compose exec app php artisan tinker        # repl
```

If your task 2 solution needs a queue worker or scheduler running, start them in a separate terminal — the Compose stack does not run them by default:

```
docker compose exec app php artisan queue:work
docker compose exec app php artisan schedule:work
```

`compose.yaml` also includes a commented-out Mailpit service (web UI on <http://localhost:8025>) if you want to inspect outgoing mail; uncomment it and set `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025` in `.env`.

An in-memory sqlite database is configured for the test suite (`phpunit.xml`); no extra setup is required for `php artisan test` to run.

If you prefer to work without Docker that is fine too; you'll need PHP 8.2+, Composer, and the `pdo_sqlite` extension. Solutions are still evaluated on the tests you write, regardless of how you run them.

## The Tasks

We are mindful of your time and the tasks below are overly simplified to accommodate various valid solutions. Your solutions will be evaluated on your use of tests as well as your implementation and understanding of Laravel conventions, you do not need to "impress" us with everthing you know or coding "clever" solutions. Work with the structure provided and feel free to get in touch if there are any gaps.

If you feel you would approach any of these tasks differently given more time, please provide this as part of your submission.

If you have any questions before you start any of these tasks, please email tech-test@aussiebb.com.au.

### Task 1 - Expose a new api endpoint to list all applications

We need to expose a new internal api endpoint to list all applications in the system and should accept an optional plan type filter `(null, nbn, opticomm, mobile)` for user experience. This endpoint will exist only for authenticated users and will be consumed by an SPA frontend.

As part of exposing these details we only want to provide the following data:
- Application id
- Customer full name
- Address
- Plan type
- Plan name
- State
- Plan monthly cost
- Order Id (only show this field on applications with the `complete` status)

The following must also be observed
- The data returned should be paginated for scalability.
- The oldest applications must be at the top of the list.
- Plan monthly cost is stored as cents in the database, this must be displayed in human readable dollar format.

***NOTE:*** You are not required to implement any additional auth features/tests, and you can assume any/all auth associated tests are already done. You are also not required to build out the frontend as part of this task.

### Task 2 - Automate the ordering of all nbn applications

Once received and all internal business rules have been satisfied an application will move to a status of `order` (out of scope for this task).

Applications with this status can be ordered via the appropriate B2B integration for the plan type and if successful will continue through the processes. For this task, you will be required to identify and process any `nbn` application with the following business logic:
- a. Must pick up and process `nbn` applications every 5 minutes.
- b. Only applications with the status `order` should be processed.
- c. Each application must be processed on a queue (assume queue worker is configured).
- d. Must store the Order Id on the application and progress to a `complete` status if successful.
- e. Progress to `order failed` status in the event of a failed order or error.

You are required to send a `Http::post` request to the B2B endpoint (an `NBN_B2B_ENDPOINT` environment variable exists for this purpose) with the following application and plan details:
- address_1
- address_2
- city
- state
- postcode
- plan name

***NOTE:*** You should not send any actual http requests as part of this task, a sample successful and failure response can be found in `test\stubs\nbn-successful-response.json` and `test\stubs\nbn-fail-response.json`.

***NOTE:*** B2B = business to business api

## Submissions

Your solutions must be submitted by emailing a zip/tarball (without the vendor dir and preserving git history) to 
tech-test@aussiebb.com.au.

You should not require any additional packages to complete these tasks, if you do decide to add additional packages please specify your reasoning to do so in your submission.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
