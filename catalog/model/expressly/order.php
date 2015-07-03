<?php

class ModelExpresslyOrder extends Model
{
    public function getOrderIdByCustomerAndDateRange($id, $minDate, $maxDate)
    {
        $query = $this->db->query(
            sprintf(
                'SELECT * FROM %sorder WHERE `customer_id`=%u AND `date_added` BETWEEN "%s" AND "%s"',
                DB_PREFIX,
                $id,
                $minDate,
                $maxDate
            )
        );

        return $query->row;
    }
}