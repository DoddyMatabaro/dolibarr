<?php
/* Copyright (c) 2026 		Franck Sefu 				<francksefu1998@gmail.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/custom/creditmanager/core/class/html.formcreditmanager.class.php
 *      \ingroup    core
 *      \brief      Class file for html component credit manager
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

/**
 *      Class to manage building of HTML components
 */
class FormCreditTypes extends Form
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	public $errors = array();


	/**
	 *    Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    	/**
	 * Returns an array with projects qualified for a third party
	 *
	 *non @param int 		$socid 			Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 * @param int 		$selected 		Id project preselected
	 * @param string 	$htmlname 		Name of html component
	 * @param int 		$maxlength 		Maximum length of label
	 * non @param int 		$option_only 	Return only html options lines without the select tag
	 * non @param int|string	$show_empty Add an empty line
	 * non @param int 		$discard_closed Discard closed projects (0=Keep,1=hide completely,2=Disable)
	 * non @param int 		$forcefocus 	Force focus on field (works with javascript only)
	 * @param int 		$disabled 		Disabled
	 * @param int 		$mode 			0 for HTML mode and 1 for array return (to be used by json_encode for example)
	 * @param string 	$filterkey 		Key to filter on title or ref
	 * non @param int 		$nooutput 		No print output. Return it only.
	 * non @param int 		$forceaddid 	Force to add project id in list, event if not qualified
	 * @param string	$htmlid 		Html id to use instead of htmlname
	 * @param string 	$morecss 		More CSS
	 * @param string 	$morefilter 	More filters (Must be a sql sanitized string)
	 * @return int|string|array<array{key:int,value:string,ref:string,labelx:string,label:string,disabled:bool}>         HTML string or array of option or <0 if KO
	 */
	public function select_credit_types_list($selected = '', $htmlname = 'credittypeid', $maxlength = 100, $disabled = 0, $mode = 0, $filterkey = '', $htmlid = '', $morecss = 'maxwidth500', $morefilter = '', $typeOfReturn = null)
	{
		// phpcs:enable
		global $user, $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/custom/creditmanager/class/CreditType.class.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

		if (empty($htmlid)) {
			$htmlid = $htmlname;
		}

        $form = new Form($this->db);
		$out = '';
		$outarray = array();

		$hideunselectables = false;

        $sql = "SELECT c.rowid, c.label, c.code";
		$sql .= " FROM " . $this->db->prefix() . "credits_types as c;";

		$resql = $this->db->query($sql);

        $num = $this->db->num_rows($resql);
        $arrayofAllCreditType = [];
		$i = 0;

        if ($num) {
			while ($i < $num) {
			    $obj = $this->db->fetch_object($resql);
                $arrayofAllCreditType[] = array(
                    'rowid' => (int) $obj->rowid,
					'label' => $obj->label,
                    'code' => $obj->code,
                );
                $i++;
            }
        }
        $arrayForSelectCreditType = [];
		$arrayForSelectCreditType[0] = $langs->trans("");
        foreach($arrayofAllCreditType as $credit) {
            $arrayForSelectCreditType[$credit['rowid']] = $langs->trans($credit["label"]);
        }

		if ($typeOfReturn) {
			return $arrayForSelectCreditType;
		}
        print $form->selectarray($htmlname, $arrayForSelectCreditType, $selected == 0 ? $htmlid : $selected, $selected, 0, 0, '', 0, 0, 0, '', 'onrightofpage width200');

	}
}