### Mautic MessageFusion Plugin

This plugin enable Mautic 5 to run MessageFusion as an email transport. Features:
- API transport. This transport can send up to 2000 emails per API request which makes it very fast compared to SMTP.
- Bounce webhook handling. This plugin will unsubscribe contacts in Mautic based on the hard bounces while MessageFusion will take care of the soft bounce retrieals.


#### Mautic Mailer DSN Scheme
`mf+api`

#### Mautic Mailer DSN Example
`'mailer_dsn' => 'mf+api://:<api_key>@default',`
- api_key: Get MessageFusion API key from https://messagefusion.org
