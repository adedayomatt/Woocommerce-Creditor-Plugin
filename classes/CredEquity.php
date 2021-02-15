<?php 
require __DIR__.'/Http.php';

class CredEquity extends Http{

    protected $headers;


    public function __construct()
    {
        parent::__construct();
        $this->headers = [
            'Access-Key' => CredConfig::CRED_ACCESS_KEY(),
            'Content-Type' => 'application/json'
        ];
    }

    public function lookUp($phone)
    {
        try{
            $request = $this->post(CredConfig::CRED_API_URL(), ['PhoneNo' => $phone], $this->headers);
            return $request->getBody();
        }
        catch(Exception $e){
            return $e->getMessage();
        }
    }

}