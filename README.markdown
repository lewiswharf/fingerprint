##Fingerprint

Fingerprint allows you to create a fingerprint of the hidden inputs for a given form, ensuring that users do not change the values of the hidden inputs prior to submitting the form.

##Usage

Fingerprint works with Symphony section events. Custom events that do not invoke the frontend delegate `EventPreSaveFilter` will not work and be ignored.

1. Enable the extension.
2. Set a long and random secret under Preferences.

If the fingerprint upon form submission fails to match the fingerprint created at page creation, the filter messages array will be populated and cause the event to fail.

    <filter name="fingerprint" status="failed">Fingerprint does not match.</filter>

*Note: hidden input values must be generated via XSLT. Hidden inputs added or changed with JavaScript will cause the event to fail.*

## Example Use Cases

Fingerprint allows you to do the following things without worrying about users altering values and tampering with a form:

1. Calculate shopping cart prices/totals via XSLT and pass them as a hidden input to the payment processor.
2. Use in conjunction with the Members extension to ensure users do not change their role or attempt to edit another entry.
3. Use in conjunction with the Stripe extension to ensure information sent to Stripe is not altered by the user.