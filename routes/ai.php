<?php

use App\Mcp\Servers\FrontendSenseiServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('frontend-sensei', FrontendSenseiServer::class);
