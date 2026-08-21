<?php

use App\Broadcasting\UserNotificationsChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', new UserNotificationsChannel);
