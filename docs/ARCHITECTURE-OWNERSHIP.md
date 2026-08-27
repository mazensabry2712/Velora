# Module Ownership

| Module | Application | Domain | Infrastructure |
|---|---|---|---|
| Booking | Use cases / DTOs | booking rules / contracts | Eloquent / external adapters |
| Queue | admission / transitions | queue contracts / invariants | queue repository |
| Subscription | overview / limits / upgrades | subscription contracts | billing persistence / notifications |
| Pricing | country-price use cases | pricing contracts | price selector |
| Tenant | registration / provisioning use cases | tenant contracts | Stancl / persistence |
| Reporting | report use cases | report contracts | query/read adapters |
| Presentation | controllers / requests | none | view composers |

Controllers are not module owners. They are transport adapters into Application use cases.
