### Mautic messagefusion Plugin

This plugin enable Mautic 5 to run messagefusion as an email transport. Features:
- API transport. This transport can send up to 2000 emails per API request which makes it very fast compared to SMTP.
- Bounce webhook handling. This plugin will unsubscribe contacts in Mautic based on the hard bounces while messagefusion will take care of the soft bounce retrieals.


#### Mautic Mailer DSN Scheme
`mautic+messagefusion+api`


