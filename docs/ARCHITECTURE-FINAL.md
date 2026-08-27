# Architecture Final Target

```text
Interfaces
   -> Application Use Cases
      -> Domain Contracts / Rules
         -> Infrastructure Adapters
            -> Central or Tenant Persistence
```

Use this structure for all new features. Existing legacy paths are migrated only after regression coverage is established.
