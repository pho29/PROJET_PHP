<?php
require_once __DIR__ . '/../models/MatchDAO.php';
require_once __DIR__ . '/../models/JoueurDAO.php';

class StatistiquesController {

    private $matchDAO;
    private $joueurDAO;

    public function __construct() {
        $this->matchDAO = new MatchDAO();
        $this->joueurDAO = new JoueurDAO();
    }

    public function getStatsGlobales() {
        return $this->matchDAO->getStatsMatchs();
    }

    public function getStatsJoueurs() {
        return $this->joueurDAO->getStatsParJoueur();
    }
    public function getSelecCons($id_joueur){
        return $this->joueurDAO->getSelectionsConsecutives($id_joueur);
    }
}
