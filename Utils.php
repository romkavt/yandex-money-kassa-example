<?php
/**
 * Created by IntelliJ IDEA.
 * User: baibik
 * Date: 18.03.15
 * Time: 12:26
 * To change this template use File | Settings | File Templates.
 */

namespace shop;


class Utils {

    public static function formatDate(\DateTime $date)
    {
        $performedDatetime = $date->format("Y-m-d") . "T" . $date->format("H:i:s") . ".000" . $date->format("P");
        return $performedDatetime;
    }

    public static function formatDateForMWS(\DateTime $date)
    {
        $performedDatetime = $date->format("Y-m-d") . "T" . $date->format("H:i:s") . ".000Z";
        return $performedDatetime;
    }

}