<?php 
class CronApi extends ApiController {
    public function clean(){
        $logKeeper = new LogKeeper();
        $logKeeper->purge(LOGTIMOUT);
    }
}