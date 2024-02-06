<?php

namespace App;
require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;

// Update the WebSocket server class namespace based on your project structure
use App\WebSocket\WebSocketServer;

$loop = Factory::create();

// Create a new Ratchet WebSocket server
$webSocketServer = new WsServer(new WebSocketServer());

// Wrap it with a React HTTP server
$httpServer = new HttpServer($webSocketServer);

// Bind the server to port 8080
$server = new Server('0.0.0.0:8080', $loop); // Use 0.0.0.0 to listen on all available network interfaces
$server->listen($httpServer);

echo "WebSocket server running on port 8080...\n";

$loop->run();
