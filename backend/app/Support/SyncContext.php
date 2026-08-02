<?php

namespace App\Support;

/**
 * Guards model observers from echoing webhook-originated writes back to Firestore.
 */
class SyncContext
{
    public static bool $fromFirestore = false;
}
