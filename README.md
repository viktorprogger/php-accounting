----------
# This composer module is in development. Please give it a star or subscribe to releases to be notified about development process.
----------

This module is a framework-agnostic base to make
your own specific accounting module.

You need to manually implement all data models and some interfaces
to use the functionality of internal accounting.

See this documentation to get into the idea of how tp organize
your internal accounting system.

-----------

### Models and basic workflow

You need to create 3 different data models:
- Account. Its goal is to store information about its funds. There may be at least two types of accounts:
    - Regular account. It's just a regular account :)
    - Blackhole account. The difference is that Blackhole Account:
        - doesn't store any funds
        - has not any validation on transacting funds from it
- Invoice. This model stores information about the sum and accounts from and to which those funds are transitioning. Of course it may store some additional info such as date/time of creation, some transitioning reason, product id, etc. Invoice must have exactly 4 states:
    - Created. This is the state ehn the invoice is just created and there are no any operation with funds. Imagine the situation: you formed an e-commerce order, but didn't pay it and even didn't have a confirmation from the seller. This situation is for `created` Invoice.
    - Held. Now you've got confirmation from the seller. The seller wants to know if you has enough funds to pay. This situation is ro make the Invoice `held`. The funds are holding on your account. Funds are still yours, but now you can't use this sum for something else except to pay this order.
    - Complete. Now you have your products, and we need to give the funds to the seller. The way to do this is to move held by the Invoice funds from your account to sellers account and mark the Invoice as `complete`
    - Canceled. Sometime there are some reasons to cancel your order. Just mark the Invoice as `canceled`. And don't forget to "defrost" held funds (if some).
- Transactions. This is an atomic movement in funds transitioning. Before every step in Invoice workflow you need to create a Transaction in state `created` and move it to state `success` on the end of this step or to `cancel` on some type of fail (i.e. if you're trying to hold funds when it is not enough on the account you're working with). With transactions you can show history of any account and build amazing charts.



### Thanks

[@Sp1ker](https://github.com/Sp1ker): Thanks for help in architecture design and some criticism.
