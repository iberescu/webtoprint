<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

// Feature tests boot Laravel and reset the DB per test.
uses(Tests\TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature');

// Unit tests stay plain PHP — fast, no Laravel boot, no DB.
