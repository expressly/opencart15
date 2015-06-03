<?php

class ModelExpresslyCountry extends Model
{
    public function getByIso3($key)
    {
        $query = $this->db->query(sprintf("SELECT * FROM %scountry WHERE iso_code_3 = '%s' AND status = '1'", DB_PREFIX,
            $key));

        return $query->row;
    }
}