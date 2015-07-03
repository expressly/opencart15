<?php

class ModelExpresslyVoucher extends Model
{
    public function getVoucherCodeByOrderId($id)
    {
        $query = $this->db->query(
            sprintf(
                'SELECT `code` FROM %order_voucher WHERE `order_id`=%u',
                DB_PREFIX,
                $id
            )
        );

        return $query->row;
    }
}