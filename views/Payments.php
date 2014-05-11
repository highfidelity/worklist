<?php

class PaymentsView extends View {
    public $layout = 'NewWorklist';
    public $title = 'PayPal MassPay Run';

    public $stylesheets = array(
        'css/payments.css'
    );

    public $scripts = array(
        'js/jquery/jquery.tablednd_0_5.js',
        'js/jquery/jquery.template.js',
        'js/jquery/jquery.jeditable.min.js',
        'js/timepicker.js',
        'js/payments.js'
    );

    public function render() {
        $this->alert_msg = $this->read('alert_msg');
        $this->fund_id = $this->read('fund_id');

        return parent::render();
    }

    public function actionPay() {
        $input = $this->read('input');
        return $input['action'] == 'pay';
    }

    public function actionConfirm() {
        $input = $this->read('input');
        return $input['action'] == 'confirm';
    }

    public function actionDefined() {
        $input = $this->read('input');
        return $input['action'] != '';
    }

    public function paymentResults() {
        return
          $this->read('message') . urldecode($this->read('pp_message'))
          . '<p><a href="./payments">Process More Payments.</a></p>';
    }

    public function fundSelectBox() {
        $fund_id = $this->read('fund_id');
        $ret =
            '<select name="fund_id" id="fund_id">'
            .  '<option value="0"' . ($fund_id == 0 ? 'selected="selected"' : '') . '>Not funded</option>';
        foreach (Fund::getFunds() as $fund) {
            $ret .=
                '<option value="' . $fund['id'] . '" ' . ($fund_id == $fund['id'] ? 'selected="selected"' : '') . '>'
                . $fund['name'] .
                '</option>';
        }
        $ret .= '</select>';
        return $ret;
    }

    public function order() {
        $input = $this->read('input');
        return $input['order'];
    }

    public function paymentsTableRows() {
        $payee_totals = $this->read('payee_totals');
        $fund_projects = $this->read('fund_projects');
        $sql_get_fund_projects = $this->read('sql_get_fund_projects');
        $fund_id = $this->read('fund_id');
        $ret = '';
        foreach ($payee_totals as $payee) {
            $ret .= '
                  <tr>
                    <td>
                      <input type="checkbox" name="'.$payee["mechanic_id"].'fees"
                        onclick="javascript:toggleCBGroup(\'fees'.$payee["mechanic_id"].'\', this);"
                        rel="' . $payee["total_amount"] . '" />
                    </td>
                    <td colspan="4" align="left">
                      <a href="javascript:void(0);" onclick="toggleVis(\'indfees'.$payee["mechanic_id"].'\')">
                        '.$payee["mechanic_nick"].'
                      </a>
                    </td>
                      <td align="right" onclick="toggleBox(\'payfee'.$payee["mechanic_id"].'\')">'.$payee["total_amount"].'</td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>';

            $fee_rows = '';
            $display_set = false;

            // Display fees for each user
            $ind_sql = "
                SELECT f.*, wl.project_id, wl.summary
                FROM
                    (".FEES." f LEFT JOIN ".USERS." u ON f.user_id = u.id)
                    LEFT JOIN ".WORKLIST." wl ON f.worklist_id = wl.id
                WHERE
                    wl.status = 'Done'
                    AND f.paid = '0'
                    AND f.withdrawn = '0'
                    AND u.paypal_verified = '1'
                    AND f.amount > 0
                    AND f.user_id = '".$payee["mechanic_id"]."'
                    AND wl.project_id IN (" . $sql_get_fund_projects . ")";
            $ind_query = mysql_query($ind_sql);
            if (mysql_num_rows($ind_query) > 0) {
                while ($ind_fees = mysql_fetch_array($ind_query)) {
                    $checked = false;
                    if (isset($_POST["action"]) && ($_POST["action"] == 'confirm') &&
                        in_array($ind_fees["id"], $_POST["payfee"])) {
                        $checked = true;
                        $display_set = true;
                    }
                    $fee_rows .= '
                        <tr>
                            <td class="fee-row"><input type="checkbox" class="fees' . $payee["mechanic_id"].'"
                              name="payfee[]" id="payfee'.$ind_fees["id"].'" value="'.$ind_fees["id"].'"
                              onclick="toggleCBChild(\'fees'.$payee["mechanic_id"].'\', this);" rel="'.$ind_fees["amount"].'"' .
                              ($checked ? ' checked="checked"' : '') . ' /></td>
                            <td>'.strftime("%m-%d-%Y", strtotime($ind_fees["date"])).'</td>
                            <td onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["id"].'</td>
                            <td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">
                                <a class="worklist-item" id="worklist-"'.$ind_fees["worklist_id"].'"
                                  href="./'.$ind_fees["worklist_id"].'" >
                                    #'.$ind_fees["worklist_id"].'
                                </a>
                            </td>
                            <td align="left">'.$fund_projects[$ind_fees["project_id"]].'</td>
                            <td align="right" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["amount"].'</td>
                            <td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["summary"].'</td>
                            <td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["desc"]."</td>
                        </tr>\r\n";
                }
            }

            if ($fund_id == 3) {
                // Display bonuses for each user
                $bonus_sql = "
                    SELECT
                        b.id AS id,
                        b.amount AS amount,
                        b.desc AS notes,
                        b.date AS date,
                        u.nickname AS payer_name
                    FROM
                        fees b
                        LEFT JOIN users u ON u.id = b.payer_id
                    WHERE
                        b.user_id = ".$payee['mechanic_id']."
                        AND b.paid=0 and b.bonus=1";
                $bonus_query = mysql_query($bonus_sql);
                if (mysql_num_rows($bonus_query) > 0) {
                    while ($ind_bonus = mysql_fetch_array($bonus_query)) {
                        $checked = false;
                        if (isset($_POST["action"]) && ($_POST["action"] == 'confirm') &&
                           in_array($ind_bonus["id"], $_POST["paybonus"])) {
                            $checked = true;
                            $display_set = true;
                        }
                        $fee_rows .= '
                            <tr>
                                <td class="fee-row"><input type="checkbox" class="fees'.$payee["mechanic_id"].'"
                                  name="paybonus[]" id="paybonus'.$ind_bonus["id"].'" value="'.$ind_bonus["id"].'"
                                  onclick="toggleCBChild(\'fees'.$payee["mechanic_id"].'\', this);" rel="'.$ind_bonus["amount"].'"' .
                                  ($checked ? ' checked="checked"' : '') . ' /></td>
                                <td>'.strftime("%m-%d-%Y", strtotime($ind_bonus["date"])).'</td>
                                <td onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">'.$ind_bonus["id"].'</td>
                                <td colspan="2" align="left" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">BONUS</td>
                                <td align="right" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">'.$ind_bonus["amount"].'</td>
                                <td align="right" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">&nbsp;</td>
                                <td align="left" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">
                                    (FROM: '.$ind_bonus['payer_name'].') '.$ind_bonus["notes"]."
                                </td>
                            </tr>\r\n";
                    }
                }
            }

            if ((mysql_num_rows($ind_query) > 0) || ($fund_id == 3 && mysql_num_rows($bonus_query) > 0)) {
                $ret .= '<tbody id="indfees'.$payee["mechanic_id"].'"';
                if ($display_set == false) {
                    $ret .= ' style="display: none;"';
                }
                $ret .= '>';
                $ret .= $fee_rows;
                $ret .= '</tbody>';
            }
        }
        return $ret;
    }
}
