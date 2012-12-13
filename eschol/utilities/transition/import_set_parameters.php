<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//SET KEY PARAMETERS
//

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// WHERE TO GET THE DATA FROM:
//
$importHome = '/apps/bpress/transition/cdltransition.bepress.com/cdlib/';
//$importHome= '/Users/bhui/Documents/bepressdata/'; // Barbara's local machine
//$importHome = '/apps/subi/transition/2011-09-30_transition_data/cdlib/';

// 
// WHERE TO UPLOAD THE OJS FILES (ARTICLE VERSIONS, REVIEWS, ETC):
//
$baseUploadDir = '/apps/subi/ojs/files';


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// WHICH JOURNALS TO UPLOAD & RELATED INFO, STORED IN AN ARRAY:
//
// values in array: journal path, journal prefix, OJS journal ID, chief editor email,editor lname, editor fname, editor mname
$importParentDir = array();
//TRANSITION 1 JOURNALS
$importParentDir[] = array('uclastat/cts/tise/','uclastat_cts_tise',1,'rgould@stat.ucla.edu', 'Gould', 'Rob','');
$importParentDir[] = array('ucsbspanport/textoshibridos/','ucsbspanport_textoshibridos',2, 'workman.amber@gmail.com', 'Workman', 'Amber', 'L');
$importParentDir[] = array('ssha/transmodernity/','ssha_transmodernity',3, 'lopezcalvo@msn.com', 'Lopezcalvo', 'Ignacio', '');
//TRANSITION 2 JOURNALS
//$importParentDir[] = array('???', 'ucsb_soc_jcmrs', 4); //what about this??
$importParentDir[] = array('irows/cliodynamics/', 'irows_cliodynamics', 5, 'peter.turchin@uconn.edu','Turchin','Peter', '');
$importParentDir[] = array('cmrs/comitatus/', 'cmrs_comitatus', 6, 'sullivan@humnet.ucla.edu', 'Sullivan', 'Blair', '');
$importParentDir[] = array('clic/crossroads/', 'clic_crossroads', 7, 'gfadams@ucla.edu', 'Adams','Gail', '');
$importParentDir[] = array('gseis/interactions/', 'gseis_interactions', 8, 'amyliu15@ucla.edu','Liu','Amy','');
$importParentDir[] = array('ucmercedlibrary/jca/', 'ucmercedlibrary_jca', 9, 'mweppler-selear@ucmerced.edu','Weppler-Selear','Mary','');
$importParentDir[] = array('ucmercedlibrary/jcgba/', 'ucmercedlibrary_jcgba', 10, 'mweppler-selear@ucmerced.edu','Weppler-Selear','Mary','');
$importParentDir[] = array('cmrs/litteraecaelestes/', 'cmrs_litteraecaelestes', 11, 'butler@humnet.ucla.edu','Butler','Shane','');
$importParentDir[] = array('clta/lta/', 'clta_lta', 12, 'maburns@uci.edu','Burns','Maureen','A.');
$importParentDir[] = array('uclabiolchem/nutritionbytes/', 'uclabiolchem_nutritionbytes', 13, 'larab@mednet.ucla.edu','Arab', 'Lenore','');
$importParentDir[] = array('uclabiolchem/nutritionnoteworthy/', 'uclabiolchem_nutritionnoteworthy', 14, 'eulee@mednet.ucla.edu','Lee','Eryn','Ujita');
$importParentDir[] = array('ucla_french/pg/', 'ucla_french_pg', 15, 'mbumatay@ucla.edu','French and Francophone Studies Graduate Student Association','[corporate author]','');
$importParentDir[] = array('imbs/socdyn/sdeas/', 'imbs_socdyn_sdeas', 16, 'drwhite@uci.edu','White', 'Douglas','R.');
$importParentDir[] = array('ucsb_ed/spaces/', 'ucsb_ed_spaces', 17, 'adosalmas@education.ucsb.edu','Dosalmas','Angela','');
$importParentDir[] = array('wc/worldcultures/', 'wc_worldcultures', 18, 'jpgray@uwm.edu','Gray','Pat','');
//TRANSITION 2A JOURNALS
$importParentDir[] = array('appling/ial/', 'appling_ial', 19, 'bahiyyih@ucla.edu','Hardacre','Bahiyyih','L.');
$importParentDir[] = array('ced/places/', 'ced_places', 20, 'josh@placesjournal.org','Wallaert','Josh','');
$importParentDir[] = array('ucdavislibrary/streetnotes/', 'ucdavislibrary_streetnotes', 21, 'michalski@ucdavis.edu','Michalski','David','');
//TRANSITION 3 JOURNALS
$importParentDir[] = array('acgcc/jtas/', 'acgcc_jtas', 22, 'emartinsen@vcccd.edu','Martinsen','Eric','L');
$importParentDir[] = array('ucmp/paleobios/', 'ucmp_paleobios', 23, 'mark@berkeley.edu','Goodwin','Mark','B.');
$importParentDir[] = array('uclapsych/ijcp/', 'uclapsych_ijcp', 24, 'sana88@ucla.edu','Ahmad','Sana','');
$importParentDir[] = array('uclalib/egj/', 'uclalib_egj', 25, 'majankowska@library.ucla.edu','Jankowska','Maria','A.');
$importParentDir[] = array('ucla_spanport/mester/', 'ucla_spanport_mester', 26, 'covadonga@ucla.edu','Lamar Prieto','Covadonga','');
$importParentDir[] = array('ucla_spanport/gradproceedings/', 'ucla_spanport_gradproceedings', 27, 'craymond@ucla.edu','Chase','Raymond','');
$importParentDir[] = array('ucla_history/historyjournal/', 'ucla_history_historyjournal', 28, 'naomit@ucla.edu','Taback','Naomi','');
$importParentDir[] = array('ucla_cjs/kheshbn/', 'ucla_cjs_kheshbn', 29, 'koralm@ca.rr.com','Koral','Miriam','');
$importParentDir[] = array('uciem/westjem/', 'uciem_westjem', 30, 'westjem@gmail.com','Chang','Rex','');
$importParentDir[] = array('uccllt/l2/', 'uccllt_l2', 31, 'usreeb@gmail.com','Bhattacharya','Usree','');
$importParentDir[] = array('ucbgse/bre/', 'ucbgse_bre', 32, 'idpareto@berkeley.edu','Domínguez-Pareto, Editor','Irenka','f');
$importParentDir[] = array('ucbgerman/transit/', 'ucbgerman_transit', 33, 'kurtbeals@gmail.com','Beals','Kurt','');
$importParentDir[] = array('tht/newplaywrights/', 'tht_newplaywrights', 34, 'alexandermaggio@gmail.com','last','first','middle');
$importParentDir[] = array('sp_ptg_ucb/lucero/', 'sp_ptg_ucb_lucero', 35, 'cuellar@berkeley.edu','Cuellar','Manuel','R');
$importParentDir[] = array('our/buj/', 'our_buj', 36, 'paigedr@gmail.com','Dunn-Rankin','Paige','');
$importParentDir[] = array('our/bsj/', 'our_bsj', 37, 'tritgarg@gmail.com','Garg','Trit','');
$importParentDir[] = array('nelc/uee/', 'nelc_uee', 38, 'wendrich@humnet.ucla.edu','Wendrich','Willeke','');
$importParentDir[] = array('jmie/sfews/', 'jmie_sfews', 39, 'snluoma@ucdavis.edu','Luoma','Samuel','N');
$importParentDir[] = array('italian_ucla/carteitaliane/', 'italian_ucla_carteitaliane', 40, 'carteitaliane@gmail.com','Carte Italiane','','');
$importParentDir[] = array('ismrg/cisj/', 'ismrg_cisj', 41, 'marisa@berkeley.edu','Escolar','Marisa','');
$importParentDir[] = array('international/asc/ufahamu/', 'international_asc_ufahamu', 42, 'rayed@ucla.edu','Khedher','Rayed','');
$importParentDir[] = array('germanic/newgermanreview/', 'germanic_newgermanreview', 43, 'dbarr47@ucla.edu','Barry','David','');
$importParentDir[] = array('ethnomusic/pre/', 'ethnomusic_pre', 44, 'pre@ucla.edu','Clark','Logan','');
$importParentDir[] = array('cssd/opolis/', 'cssd_opolis', 45, 'shayna.conaway@ucr.edu','Conaway','Shayna','');
$importParentDir[] = array('anrcs/californiaagriculture/', 'anrcs_californiaagriculture', 46, 'jlbyron@ucdavis.edu','Byron','Janet','');
// FOR TESTING:
//echo "importParentDir:\n";
//print_r($importParentDir);
//die("\ntesting!\n");

?>
