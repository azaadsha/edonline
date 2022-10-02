Commerce Payment Extra Order
-------

## Configuration
1. Go to /admin/commerce/config/payment/extra-order to configure this module.
2. If you want to configure auto capture/void on order transition check corresponding checkboxes and submit.
    - Processing of auto-capture/void happens via advanced queue. Add `drush advancedqueue:queue:process commerce_payment_extra_order` to your crontab.
3. If you want for the order to be placed automatically if there's a payment registered with sufficient authorization
select applicable payment gateways from the list and enable cron. Set the window in which we'll be looking
for applicable orders. Min threshold should give user time to complete the order by visiting order completion page (30
minutes is reasonable). Max threshold makes sure we don't go over all abandoned orders.

## Usage
Go to /admin/config/system/queues/jobs/commerce_payment_extra_order to see all items in queue.
Those are payments that are being canceled/captured, based on your configuration preferences
(by default all automations are being disabled). Go to configuration to enable auto capture/void.
