# Mautic Messagefusion Transport Bundle

This repository contains the **Messagefusion Transport Bundle** for Mautic, which integrates the Messagefusion API with Mautic's email sending system. This plugin provides a custom transport for sending emails using the Messagefusion API, enhancing email sending capabilities in Mautic with additional features like advanced logging and metadata handling.

## Features

- Custom email transport integration with Mautic via Messagefusion API.
- Supports authentication using an API key for secure email delivery.
- Advanced logging for troubleshooting and debugging, including API response details.
- Handles advanced email metadata, substitution tokens, inline CSS, and attachments.
- Supports multiple regions for Messagefusion's API, including US and EU data centers.
- Includes a robust error handling mechanism to ensure reliable email delivery.

## Installation

1. **Download or Clone the Repository**

   Clone this repository into the `plugins` directory of your Mautic installation:

   ```bash
   git clone https://github.com/Swaraa-Tech-Solutions-LLP/mautic-messagefusion.git

into the `plugins/` directory, and rename the new directory to `MessagefusionBundle`.

#### Mautic Mailer DSN Scheme
`mf+api`


