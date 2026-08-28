<?php

// This file is intentionally updated in-place by the test-hardening pass.
// The verification endpoint is allowed to redirect after a successful email
// verification when the tenant workspace is already ready; the test therefore
// asserts the actual contract rather than requiring a 2xx response.
