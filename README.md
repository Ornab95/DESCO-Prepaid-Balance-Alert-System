# Api-call
# DESCO Prepaid Balance Alert System

This PHP script monitors the balance of a DESCO prepaid electricity meter and sends an email alert if the balance falls below the average consumption over the past 7 days.

## Features

- Fetches the current balance of the DESCO prepaid meter.
- Calculates the average daily consumption over the past 7 days.
- Sends an email alert if the balance is below the 7-day average consumption.

## Requirements

- PHP 7.0 or higher
- cURL extension enabled
- An active internet connection
- A valid Gmail account for sending email alerts
