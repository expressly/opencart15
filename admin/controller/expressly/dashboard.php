<?php

require 'common.php';

class ControllerExpresslyDashboard extends CommonController
{
    private $errors = array();

    public function index()
    {
        $this->document->setTitle('Expressly');
        $this->data['breadcrumbs'] = array(
            array(
                'text' => 'Expressly',
                'href' => $this->url->link('expressly/dashboard/index'),
                'separator' => false
            )
        );
        $this->data['heading_title'] = 'Expressly';
        $this->data['text_content'] = 'Expressly Content';

        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        $this->response->setOutput($this->render());
    }

    protected function validate()
    {
        return true;
    }
}