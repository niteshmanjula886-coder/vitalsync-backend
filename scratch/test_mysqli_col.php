<?php
// Test MySQL prepare behavior with non-existent column
// If table exists but column doesn't, prepare() fails and returns false!
echo "In MySQLi, referencing a non-existent column in prepare() returns false.\n";

