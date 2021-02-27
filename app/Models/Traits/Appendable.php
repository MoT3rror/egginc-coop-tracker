<?php
namespace App\Models\Traits;

trait Appendable {

    static protected $static_appends = [];
    static protected $static_replace_appends = null;

    /**
     * set a static appends array to add to or replace the existing appends array..
     * replace => totally replaces the existing models appends array at time of calling getArrayableAppends
     * add => merges and then makes unique. when getArrayableAppends is called. also merges with the existing static_appends array
     *
     * @param $appendsArray
     * @param bool $replaceExisting
     */
    public static function setStaticAppends($appendsArray, $replaceExisting = true)
    {
        if($replaceExisting) {
            static::$static_replace_appends = true;
            static::$static_appends = array_unique($appendsArray);
        } else {
            static::$static_replace_appends = false;
            static::$static_appends = array_unique(array_merge(static::$static_appends,$appendsArray));
        }
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if(!is_null(static::$static_replace_appends)) {
            if(!static::$static_replace_appends) {
                $appends = array_unique(array_merge(static::$static_appends, $this->appends ?? []));
            } else {
                $appends = static::$static_appends;
            }
            $this->setAppends($appends);
        }
        return parent::getArrayableAppends();
    }

}