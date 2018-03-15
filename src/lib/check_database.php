<?php
// Database Connection Checker
if (!new PDO('mysql:host='.DB_HOST, '')) die('MySQL Server Failure');
