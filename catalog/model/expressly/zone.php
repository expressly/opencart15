<?php

class ModelExpresslyZone extends Model
{
    public function getByNameOrCodeAndCountry($key, $country)
    {
        $query = $this->db->query(
            sprintf(
                "SELECT * FROM %szone WHERE code = '%s' OR name LIKE '%%s%%' AND status = '1' AND country_id = %u LIMIT 1;",
                DB_PREFIX,
                $key,
                $key,
                $country
            )
        );

        return $query->row;
    }
}