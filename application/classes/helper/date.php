<?

class Helper_Date {
    /**
     * @return array
     */
    public static function yearList() {
		return Date::years(date('Y') - 50, date('Y') + 20);
	}

    /**
     * @param bool $withOngoing
     * @return array
     */
    public static function monthList($withOngoing = false) {
		$months = Date::months(Date::MONTHS_LONG);

		if($withOngoing) {
			$months = array_merge(array( 99 => Helper_Message::get('global.ongoing')), $months);
		}

		return $months;
	}

    /**
     * @param $n
     * @return mixed
     */
    public static function monthName($n) {
		$list = self::monthList();
		return Arr::get($list, $n, null);	
	}
}
