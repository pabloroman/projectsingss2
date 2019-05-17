<?php

    namespace App\Events;

    use Illuminate\Broadcasting\Channel;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Broadcasting\PrivateChannel;
    use Illuminate\Broadcasting\PresenceChannel;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    use Illuminate\Http\Request;

    class Activity{
        use InteractsWithSockets, SerializesModels;

        /**
         * Create a new event instance.
         *
         * @return void
         */
        public function __construct($request){
            $this->request = $request;
        }

        /**
         * Get the channels the event should broadcast on.
         *
         * @return Channel|array
         */
        public function broadcastOn(){
            return new PrivateChannel('channel-name');
        }
    }
