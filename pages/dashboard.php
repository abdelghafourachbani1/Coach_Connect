<?php
session_start();

if ($_SESSION['role'] === 'coach') {
// show coach dashboard
} else {
// show sportif dashboard
}