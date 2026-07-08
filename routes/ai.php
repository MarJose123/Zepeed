<?php

use App\Mcp\Servers\ZepeedServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/zepeed', ZepeedServer::class)
    ->middleware('auth:users-api');
