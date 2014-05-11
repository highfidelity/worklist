<?php

class BudgetInfo {
    public function __construct() {
    }

    /**
     * Get the budget view
     */
    public function getView() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        include(dirname(__FILE__) . "/BudgetInfo/popup-give-budget.inc");
        exit(0);
    }

    public function getViewAddFunds() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId'));
        $budget_id = (int) $_REQUEST['budgetId'];
        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            $this->respond(true, 'Returning data', array(
                'budget_id' => $budget_id,
                'seed' => $budget->seed
            ));

        } else {
            $this->respond(true, 'Invalid budget id');
        }
        exit(0);
     }
    /**
     * Get the budget update view
     */
    public function getUpdateView() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId'));
        $budget_id = (int) $_REQUEST['budgetId'];

        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            $sources = $budget->loadSources();
            $budgetClosed = !$budget->active;
            $allocated = $budget->getAllocatedFunds();
            $submitted = $budget->getSubmittedFunds();
            $paid = $budget->getPaidFunds();
            $transfered = $budget->getTransferedFunds();
            $remaining = $budget->amount - $allocated - $submitted - $paid - $transfered;
            $this->respond(true, 'Returning data', array(
                'amount' => $budget->amount,
                'closed' => $budgetClosed,
                'reason' => $budget->reason,
                'req_user_authorized' => strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false,
                'seed' => $budget->seed,
                'sources' => $sources,
                'notes' => $budget->notes,
                'remaining' => money_format('%i', $remaining),
                'allocated' => money_format('%i', $allocated),
                'submitted' => money_format('%i', $submitted),
                'paid' => money_format('%i', $paid),
                'transferred' => money_format('%i', $transfered)
            ));
        } else {
            $this->respond(true, 'Invalid budget id');
        }
    }
    /**
     * Update the budget Reason and Note
     */
    public function updateBudget() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId', 'budgetReason', 'budgetNote'));
        $budget_id = $_REQUEST['budgetId'];

        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            if ($reqUserId == $budget->receiver_id ||
                $budget->giver_id == $reqUserId) {
                $budget->notes = $_REQUEST['budgetNote'];
                $budget->reason = $_REQUEST['budgetReason'];
                if ($budget->save('id')) {
                    $this->respond(true, 'Data saved');
                } else {
                    $this->respond(false, 'Error in update budget.');
                }
            } else {
                $this->respond(false, 'You aren\'t authorized to update this budget!');
            }
        } else {
            $this->respond(false, 'Invalid budget id');
        }
    }
    /**
    Return the sum of fees that are not already paid for all the workitems linked to a specific budget
    **/
    public function getSumOfFeeNotPaidByBudget($budget_id) {
        $query = "SELECT SUM(`amount`) FROM `" . FEES .
            "` WHERE paid = 0 AND amount > 0  AND `" . FEES .
            "`.`withdrawn` != 1 AND ((worklist_id = 0 AND budget_id = " . $budget_id . ") OR worklist_id IN (SELECT id FROM " .
                WORKLIST . " WHERE budget_id = " . $budget_id . " AND status != 'Pass'))";
        $result_query = mysql_query($query);
        $row = $result_query ? mysql_fetch_row($result_query) : null;
        return !empty($row) ? $row[0] : null;
    }

    public function closeOutBudgetSource($remainingFunds, $budget, $budgetReceiver, $budgetGiver) {
            $sources = $budget->loadSources(" ORDER BY s.transfer_date DESC");
            if ($sources == null) {
                $this->respond(false, 'No source budget found!');
                return;
            }
            foreach ($sources as $source) {
                $budgetGiver = new User();
                if (!$budgetGiver->findUserById($source["giver_id"])) {
                    $this->respond(false, 'Invalid giver id.');
                }
                if ($remainingFunds < 0) {
                    if ($budget->seed != 1) {
                        $budget->updateSources($source["source_id"], - $remainingFunds);
                        $budgetGiver->updateBudget($remainingFunds, $source["budget_id"]);
                    }
                    $this->sendBudgetcloseOutEmail(array(
                        "budget_id" => $budget->id,
                        "reason" => $budget->reason,
                        "giver_id" => $source["giver_id"],
                        "receiver_id" => $budget->receiver_id,
                        "receiver_nickname" => $budgetReceiver->getNickname(),
                        "receiver_email" => $budgetReceiver->getUsername(),
                        "giver_nickname" => $budgetGiver->getNickname(),
                        "giver_email" => $budgetGiver->getUsername(),
                        "remainingFunds" => $remainingFunds,
                        "original_amount" => $budget->original_amount,
                        "amount" => $budget->amount,
                        "seed" => $budget->seed
                    ));
                    return;
                } else {
                    if ($remainingFunds > $source["amount_granted"]) {
                        $remainingFundsToGiveBack = $source["amount_granted"];
                        $remainingFunds = $remainingFunds - $source["amount_granted"];
                    } else {
                        $remainingFundsToGiveBack = $remainingFunds;
                        $remainingFunds = 0;
                    }
                    if ($budget->seed != 1) {
                        $budget->updateSources($source["source_id"], - $remainingFundsToGiveBack);
                        $budgetGiver->updateBudget($remainingFundsToGiveBack, $source["budget_id"]);
                    }
                    $this->sendBudgetcloseOutEmail(array(
                        "budget_id" => $budget->id,
                        "reason" => $budget->reason,
                        "giver_id" => $source["giver_id"],
                        "receiver_id" => $budget->receiver_id,
                        "receiver_nickname" => $budgetReceiver->getNickname(),
                        "receiver_email" => $budgetReceiver->getUsername(),
                        "giver_nickname" => $budgetGiver->getNickname(),
                        "giver_email" => $budgetGiver->getUsername(),
                        "remainingFunds" => $remainingFundsToGiveBack,
                        "original_amount" => $budget->original_amount,
                        "amount" => $budget->amount,
                        "seed" => $budget->seed
                    ));
                    if ($remainingFunds == 0) {
                        return;
                    }
                }
            }
            if ($remainingFunds != 0) {
                error_log("closeOutBudgetSource, remainingFunds not equal to 0, budget id: " . $budget->id);
            }
    }
    /**
     * Close the budget
     */

    public function closeOutBudget() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId'));
        $budget_id = $_REQUEST['budgetId'];

        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            if ($budget->active != 1) {
                $this->respond(false, 'This budget is already closed.');
                return;
            }
            if ($reqUserId == $budget->receiver_id ||
                $budget->giver_id == $reqUserId) {
                $budgetGiver = new User();
                if (!$budgetGiver->findUserById($budget->giver_id)) {
                    $this->respond(false, 'Invalid giver id.');
                    return;
                }
                $budgetReceiver = new User();
                if (!$budgetReceiver->findUserById($budget->receiver_id)) {
                    $this->respond(false, 'Invalid receiver id.');
                    return;
                }
                // all the child budgets are closed ?
                $childrenNotClosed = $budget->getChildrenNotClosed($budget->id);
                if ($childrenNotClosed == 0) {
                    // all the budgeted jobs are paid ?

                    $feeAmountNotPaid = $this->getSumOfFeeNotPaidByBudget($budget->id);
                    if ($feeAmountNotPaid === null) {
                        $remainingFunds = $budget->getRemainingFunds();
                        if ($remainingFunds >= 0) {
                            $budget->original_amount = $budget->amount;
                            $budget->amount = $budget->original_amount - $remainingFunds;
                            $budget->active = 0;
                            $budgetReceiver->updateBudget(- $remainingFunds, $budget->id, false);
                            $this->closeOutBudgetSource($remainingFunds, $budget, $budgetReceiver, $budgetGiver);
                            if ($budget->save('id')) {
                                $this->respond(true, 'Budget closed');
                            } else {
                                $this->respond(false, 'Error in update budget.');
                            }
                        } else {
                            if ($reqUserId == $budget->receiver_id) {
                                $this->respond(false, 'Your budget is spent. Please contact the grantor (' .
                                    $budgetGiver->getNickname() . ') for additional funds.');
                            } else {
                                $budget->original_amount = $budget->amount;
                                $budget->amount = $budget->original_amount - $remainingFunds;
                                $budget->active = 0;
                                $budgetReceiver->updateBudget(- $remainingFunds, $budget->id, false);
                                $this->closeOutBudgetSource($remainingFunds, $budget, $budgetReceiver, $budgetGiver);
                                if ($budget->save('id')) {
                                    $this->respond(true, 'Budget closed');
                                } else {
                                    $this->respond(false, 'Error in update budget.');
                                }
                            }
                        }
                    } else {
                        $this->respond(false, 'Some fees are not paid.');
                    }
                } else {
                    $this->respond(false, "This budget has one or more sub-allocated budget that are still active." .
                        "You may not close out this budget until the other budgets are closed out.");
                }
            } else {
                $this->respond(false, 'You aren\'t authorized to update this budget!');
            }
        } else {
            $this->respond(false, 'Invalid budget id');
        }
    }

    public function sendBudgetcloseOutEmail($options) {
        $subject = "Closed - Budget ";
        if ($options["seed"] == 1) {
            $subject = "Closed - Seed Budget ";
        }
        $subject .= $options["budget_id"] . " (For " . $options["reason"] . ")";
        $link = SECURE_SERVER_URL . "team?showUser=" . $options["receiver_id"] . "&tab=tabBudgetHistory";
        $body = '<p>Hello ' . $options["receiver_nickname"] . '</p>';
        $body .= '<p>Your budget has been closed out:</p>';
        $body .= "<p>Budget " . $options["budget_id"] . " for " . $options["reason"] . "</p>";
        $body .= "<p>Requested Amount : $" . $options["original_amount"] . "</p>";
        $body .= "<p>Allocated Amount : $" . $options["amount"] . "</p>";
        if ($options["remainingFunds"] > 0) {
            $body .= "<p>Congrats! You had a budget surplus of $" . $options["remainingFunds"] . "</p>";
        } else if ($options["remainingFunds"] == 0) {
            $body .= "<p>Good job! Your budget was right on target!</p>";
        } else {
            $body .= "<p>Your budget balance was over by $" . $options["remainingFunds"] . "</p>";
        }
        $body .= '<p>Click <a href="' . $link . '">here</a> to see this budget.</p>';
        $body .= '<p>- Worklist.net</p>';

        $plain = 'Hello ' . $options["receiver_nickname"] . '\n\n';
        $plain .= 'Your budget has been closed out:\n\n';
        $plain .= "Budget " . $options["budget_id"] . " for " . $options["reason"] . "\n\n";
        $plain .= "Requested Amount : $" . $options["original_amount"] . "\n\n";
        $plain .= "Allocated Amount : $" . $options["amount"] . "\n\n";
        if ($options["remainingFunds"] > 0) {
            $plain .= "Congrats! You had a budget surplus of $" . $options["remainingFunds"] . "\n\n";
        } else if ($options["remainingFunds"] == 0) {
            $plain .= "Good job! Your budget was right on target!\n\n";
        } else {
            $plain .= "Your budget balance was over by $" . $options["remainingFunds"] . "\n\n";
        }
        $plain .= 'Click ' . $link . ' to see this budget.\n\n';
        $plain .= '- Worklist.net\n\n';

        if (!send_email($options["receiver_email"], $subject, $body, $plain)) {
            error_log("BudgetInfo: send_email failed on closed out budget");
        }
        if ($options["remainingFunds"] < 0 || $options["seed"] == 1) {
            if (!send_email($options["giver_email"], $subject, $body, $plain)) {
                error_log("BudgetInfo: send_email failed on closed out budget");
            }
        }
    }

    /**
     * Check that all the @fields were sent on the request
     * returns true/false.
     *
     * @fields has to be an array of strings
     */
    public function validateRequest($fields, $return=false) {
        // If @fields ain't an array return false and exit
        if (!is_array($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (!isset($_REQUEST[$field])) {
                // If we specified that the function must return do so
                if ($return) {
                    return false;
                } else { // If not, send the default reponse and exit
                    $this->respond(false, "Not all params supplied.");
                }
            }
        }
    }
    /**
     * Sends a json encoded response back to the caller
     * with @succeeded and @message
     */
    public function respond($succeeded, $message, $params=null) {
        $response = array('succeeded' => $succeeded,
                          'message' => $message,
                          'params' => $params);
        echo json_encode($response);
        exit(0);
    }
}

