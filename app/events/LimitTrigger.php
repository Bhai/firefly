<?php

/**
 * Class LimitTrigger
 */
class LimitTrigger
{

    /**
     * This trigger returns false when a limit with the same
     * date / component combination already exists.
     *
     * @param Limit $limit
     */
    public function createLimit(Limit $limit)
    {
        $count = Limit::whereComponentId($limit->component_id)->whereDate($limit->date)->count();
        return $count == 0;


    }


    /**
     * Make the triggers.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(Illuminate\Events\Dispatcher $events)
    {
        $events->listen('eloquent.creating: Limit', 'LimitTrigger@createLimit');
    }

}
