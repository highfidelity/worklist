<?php

$emailTemplates = array(

/*
changed_pass
    notification of changed password
    used in: resetpass

replacement data:
    app_name - human-reqadable application name
*/

'changed_pass' => array(

'subject' => 'Worklist password changed!',

'body' =>
'<p>Change is good!</p>
<p>You have successfully changed your password with {app_name}</p>
<p>
Worklist / High Fidelity</p>
',
),

/*
recovery
    email for password recovery
    used in: forgot

replacement data:
    url - link to reset password
*/

'recovery' => array(

'subject' => 'Worklist: reset your password',

'body' =>
'<p>Hi,</p>
<p>Please click on the link below or copy and paste the url in browser to reset your password.<br/>
{url}</p>
<p>Cheers,<br/>
Worklist / High Fidelity</p>',
),

/*
feedback
    feedback received from feedback slidout
    used in: feedback

replacement data:
    app_name - human-reqadable application name
    message - message from user
*/

'feedback' => array(

'subject' => 'Feedback for {app_name}.',

'body' =>
'<p>You received feedback for {app_name} from {sender}!</p>
<p>{message}<p/>',
),

/*
bonus
    inform user they have received a bonus
    used in: pay-bonus

replacement data:
    amount - the amount of bonus received
    reason - the reason the bonus was given
*/
'bonus_received' => array(
    'subject' => 'Bonus payment of ${amount}',
    'body' => '
        <p>You received a bonus of ${amount} and note:</p>
        <p>{reason}</p>'
),

/*
welcome
    send confirmed user a welcome email
    used in: confirmation

replacement data:
    nickname - nickname of the receiving user
*/
'welcome' => array(
    'subject' => 'Getting started with Worklist',
    'body' => '
        Thank you for joining Worklist, {nickname}!<br/><br/>
        We\'re excited to have you join us as we build a new virtual world platform.<br/><br/>
        Three things to help you get started:<br/><br/>
          - Browse the source code for our various projects (the primary one is hifi): <a href="https://github.com/highfidelity/">http://github.com/highfidelity</a><br/>
          - Join us in our <a href="http://gitter.im/highfidelity/hifi">public chat room</a>.<br/>
          - Check out our C++ <a href="https://github.com/highfidelity/hifi/wiki/Coding-Standard">coding standards</a> for the hifi project.
        <br/>
        <br/>
        Thanks for joining, and we hope to see you committing code soon!
        <br/>
        <br/>
        Cheers,<br/>
        Worklist / High Fidelity<br/><br/>
        P.S. Follow our continuing adventures: <a href="http://twitter.com/theworklist">@theworklist</a> and <a href="http://twitter.com/highfidelityinc">@highfidelityinc</a>
    '
),

/*
trusted
    send approved user a notification email
    used in: userinfo
*/
'trusted' => array(
    'subject' => 'You have been Trusted!',
    'body' => '<p>Congrats!</p>
               <p>You have been trusted by one of your peers on Worklist!</p>
<p></p>
<p>See your User Profile here: {link}
</p>
<p>
Cheers,<br>
Worklist / High Fidelity</p>'
),

/*
w9-approved
    send approved user a notification email
    used in: userinfo
*/
'w9-approved' => array(
    'subject' => 'Worklist: your W-9 has been approved',
    'body' => '<p>Hello,</p>
               <p>Your W-9 form has been approved.</p>
            <p>
        Cheers,<br/>
        Worklist / High Fidelity</p>'
),

/*
w9-rejected
    send user a notification email that their w9 was rejected
    used in: userinfo

replacement data:
    reason - the reason that the w9 was rejected, as entered by the user
*/
'w9-rejected' => array(
    'subject' => 'Your W9 has been rejected',
    'body' => '<p>Hello,</p>
               <p>Your W9 form has been rejected because:</p>
               <p>{reason}</p>
        <p>
        Regards,<br/>
        Worklist / High Fidelity</p>'
),

/*
 * project-created
 *     send project creator the details associated to the newly created project
 *
 * replacement data:
 *     nickname: User's nickname
 *     project_name: Name of the project
 *     database_user: Name of mysql username
 */
'project-created-newsb' => array(
    'subject' => 'New project {project_name} added to Worklist!',
    'body' => '<p>Hi {nickname}!</p>
               <p>Your project {project_name} has been created on Worklist, with a sample \'Hello World\' index page with data from your database.<br/>
               The setup includes:</p>
               <p>1. An SVN repository available at <a href="http://svn.worklist.net/listing.php?repname={project_name}">http://svn.worklist.net/listing.php?repname={project_name}</a></p>
               <p>2. A MySQL database including a sample table. You may access your development environment database using the following details:<br/>
               Host: staging-mysql1.worklist.net<br/>
               Database: dev_{project_name}<br/>
               Username: dev_{database_user}<br/>
               Password: unsecure<p/>
               <p>3. A sandbox development environment.  You will receive another email with all required details to access your Sandbox environment shortly.</p>
               <p>4. A working copy of your site can be viewed at <a href="http://staging.worklist.net/{project_name}">http://staging.worklist.net/{project_name}</a></p>
               <p>5. You can view your development site pointing your web browser to http://dev.worklist.net/~{nickname}/{project_name}</p>
               <p>6. To update details specific to your project, or to modify project roles, go to your <a href="' . WORKLIST_URL . '{project_name}">project page</a>.</p>
               <p>7. Your project is set to allow anybody to code review by default, you can change this setting by <a href="' . WORKLIST_URL . '{project_name}?action=edit">editing your project</a>.</p>
               <p><a href="'.SERVER_URL.'>www.worklist.net</a></p>'
),

'project-created-existingsb' => array(
    'subject' => 'New project {project_name} added to Worklist!',
    'body' => '<p>Hi {nickname}!</p>
               <p>Your project {project_name} has been created on Worklist, with a sample \'Hello World\' index page with data from your database.<br/>
               The setup includes:</p>
               <p>1. An SVN repository available at <a href="http://svn.worklist.net/listing.php?repname={project_name}">http://svn.worklist.net/listing.php?repname={project_name}</a></p>
               <p>2. A MySQL database including a sample table. You may access your development environment database using the following details:<br/>
               Host: staging-mysql1.worklist.net<br/>
               Database: dev_{project_name}<br/>
               Username: dev_{database_user}<br/>
               Password: unsecure<p/>
               <p>3. We have checked out your new project into your existing sandbox. Please use your existing credentials.</p>
               <p>4. A working copy of your site can be viewed at <a href="http://staging.worklist.net/{project_name}">http://staging.worklist.net/{project_name}</a></p>
               <p>5. You can view your development site pointing your web browser to http://dev.worklist.net/~{nickname}/{project_name}</p>
               <p>6. To update details specific to your project, or to modify project roles, go to your <a href="' . WORKLIST_URL . '{project_name}">project page</a>.</p>
               <p>7. Your project is set to allow anybody to code review by default, you can change this setting by <a href="' . WORKLIST_URL . '{project_name}?action=edit">editing your project</a>.</p>
               <p><a href="'.SERVER_URL.'">worklist.net</a></p>'
),

'project-created-github' => array(
    'subject' => 'New project {project_name} added to Worklist!',
    'body' => '<p>Hi {nickname}!</p>
               <p>Your project {project_name} has been created on Worklist and is linked to your GitHub space.<br/><br/>
               Details:</p>
               <p>1. Your GitHub repository URL is: <a href="{github_repo_url}">{github_repo_url}</a></p>
               <p>2. To update details specific to your project, or to modify project roles, go to your <a href="' . WORKLIST_URL . '{project_name}">project page</a>.</p>
               <p>3. Your project is set to allow anybody to code review by default, you can change this setting by <a href="' . WORKLIST_URL . '{project_name}?action=edit">editing your project</a>.</p>
               <p>4. More information about Worklist.net and being a project founder can be found on our Help page <a href="' . WORKLIST_URL . 'help">here</a>.</p>
               <p><a href="'.SERVER_URL.'">worklist.net</a></p>'
),

'ops-project-created' => array(
    'subject' => 'New project {project_name} added to the Worklist!',
    'body' => '<p>Hi there</p>
               <p>Just wanted to let you know that a new project has been created on the worklist.</p>
               <p>Project Admin: {nickname}<br/>
               Project Name: {project_name}<br/>
               Project Repo type:{repo_type}
               </p>
               <p>You can view the details <a href="' . WORKLIST_URL . '{project_name}">here</a>.'
),

'forked-repo' => array(
    'subject' => 'New fork {project_name} created on your GitHub account',
    'body' => '<p>Hi {nickname}!</p>
               <p>A fork of the project {project_name} has been created on your GitHub account. Make sure you keep this fork up to date with our master repository to guarantee you\'re always working on the most recent version of the codebase</p>
               <p>In order for your locally cloned repository to keep up to date with master changes, please follow these instructions:</p>
               <ol>
               <li>Locally clone your fork: git clone {users_fork}</li>
               <li>From your cloned copy, set the master repo as upstream: git remote add upstream {master_repo}</li>
               </ol>
               <p>If you have any questions, look for assistance in our Chat.</p>
               <p>The Worklist</p>'
),

'branch-created' => array(
    'subject' => 'New branch {branch_name} created for repo {users_fork} on your GitHub account',
    'body' => '<p>Hi {nickname}!</p>
               <p>We have created a new branch {branch_name} on your {users_fork} repo in your GitHub account.</p>
               <p>Please refer to the following basic commands to work with your new branch:</p>
               <ol>
               <li>Locally clone your fork: git clone {users_fork}</li>
               <li>From your cloned copy, set the master repo as upstream: git remote add upstream {master_repo}</li>
               <li>Make sure you\'re using the latest code: git pull upstream master</li>
               <li>Checkout the correct branch: git checkout {branch_name}</li>
               <li>Make all required changes, commit to your local clone as you advance, and push to your fork whenever ready: git push</li>
               <li>Before code or functional reviews can happen, you must have pushed your progress to your GitHub fork.</li>
               </ol>
               <p>If you have any questions, look for assistance in our Chat.</p>
               <p>Worklist / High Fidelity</p>'
),
'branch-created-sub' => array(
    'body' => '<br />
               <strong>New branch {branch_name} created for repo {users_fork} on your GitHub account</strong>
               <p>We have created a new branch {branch_name} on your {users_fork} repo in your GitHub account.</p>
               <p>Please refer to the following basic commands to work with your new branch:</p>
               <ol>
               <li>Locally clone your fork: git clone {users_fork}</li>
               <li>From your cloned copy, set the master repo as upstream: git remote add upstream {master_repo}</li>
               <li>Make sure you\'re using the latest code: git pull upstream master</li>
               <li>Checkout the correct branch: git checkout {branch_name}</li>
               <li>Make all required changes, commit to your local clone as you advance, and push to your fork whenever ready: git push</li>
               <li>Before code or functional reviews can happen, you must have pushed your progress to your GitHub fork.</li>
               </ol>
               <p>If you have any questions, look for assistance in our Chat.</p>'
),

'commit-howto' => array(

),
'project-codereviewer-added' => array(
   'subject' => 'Worklist: Added as a Code Reviewer to Project',
   'body' =>
       '<p>Hi {nickname}</p>
       <p>Congrats! You have been granted Code Review rights for the following project:<br />
       <a href="{projectUrl}">{projectName}</a><br />
       Project Founder: <a href="{projectFounderUrl}">{projectFounder}</a></p>
       <p>Please contact the project founder with any questions.</p>
       <p>Cheers,<br/>
       Worklist / High Fidelity</p>'
),
'project-runner-added' => array(
    'subject' => 'Worklist: Added as a Designer to the {projectName} project',
    'body' =>
        '<p>Hi {nickname}</p>
        <p>Congrats! You have been granted Designer rights for the following project:<br />
        <a href="{projectUrl}">{projectName}</a><br />
        Project Founder: <a href="{projectFounderUrl}">{projectFounder}</a></p>
        <p>Please contact the project founder with any questions.</p>
        <p>
        Cheers,<br/>
        Worklist / High Fidelity</p>'
),

'project-runner-removed' => array(
    'subject' => 'Worklist: Designer rights removed for the {projectName} project',
    'body' =>
        '<p>Hi {nickname}</p>
        <p>Your Designer rights have been removed for the following project:<br />
        <a href="{projectUrl}">{projectName}</a><br />
        Project Founder: <a href="{projectFounderUrl}">{projectFounder}</a></p>
        <p>Please contact the project founder with any questions.</p>
        <p>- Worklist.net</p>'
),
'project-codereview-removed' => array(
        'subject' => 'Worklist: Code Review rights removed for the {projectName} project',
        'body' =>
        '<p>Hi {nickname}</p>
        <p>Your Code Review rights have been removed for the following project:<br />
        <a href="{projectUrl}">{projectName}</a><br />
        Project Founder: <a href="{projectFounderUrl}">{projectFounder}</a></p>
        <p>Please contact the project founder with any questions.</p>
        <p>- Worklist.net</p>'
),
'project-inactive' => array(
    'subject' => 'Worklist: Project set as inactive',
    'body' =>
        '<p>Hi {owner}!</p>
        <p>Your project, <a href="{projectUrl}">{projectName}</a> has not shown any activity in 90+ days.</p>
        <p>We will be setting your project as inactive, but will retain all data including repository, database and sandboxes.</p>
        <p>Should you elect to add any new jobs for <a href="{projectUrl}">{projectName}</a> in the future, you can still do so – just make sure the Project Select active only checkbox is deselected when adding your job.</p>
        <p>Once a job reaches working status, your project will automatically be reactivated again!</p>
        <p></p>
        <p>Please email <a href="mailto:' . SUPPORT_EMAIL . '">' . SUPPORT_EMAIL . '</a> with any issues or concerns.</p>
        <p>Cheers,<br/>
        <p>Worklist / High Fidelity</p>'
),
'project-removed' => array(
    'subject' => 'The {projectName} project was removed',
    'body' =>
        '<p>Hi {owner}!</p>
        <p>Your project, <a href="{projectUrl}">{projectName}</a> was added on {creation_date}. Since that time, no jobs have been added.</p>
        <p>We are removing this project from the Worklist. Should you wish to pursue this in the future please
           feel free to re-add the project and we will be more than happy to work with you!</p>
        <p></p>
        <p>Pease email <a href="mailto:' . SUPPORT_EMAIL . '">' . SUPPORT_EMAIL . '</a> with any issues or concerns.</p>
        <p></p>
        <p>- Worklist.net</p>'
),
'user-signups' => array(
    'subject' => 'New Worklist users in last {hours} hrs',
    'body' => '
        <p>New users (in last {hours}hrs):</p>
        {userList}
    '
),
'workitem-mention' => array(
    'subject' => '#{job_id} {summary}',
    'body' => '
        {author} mentioned you:<br /><br />
        {text}<br /><br />
        {link}
    '
),
'jobs-weekly-report' => array(
    'subject' => 'Worklist Weekly',
    'body' => '
    <h2>
      Summary of Worklist activity for the past week:
    </h2>
    <table>
      {data}
    </table>',
    'plain' => '
    ## Summary of Worklist activity for the past week:

    {text}'
),
'job-assigned' => array(
    'subject' => 'Assigned Job #{job_id} - {summary}',
    'body' => '
      <p>Hi {assigned}</p>
      <p>
        Just wanted to let you know that {assigner} has assigned you to job:
        <a href="' . WORKLIST_URL . '{job_id}">{job_id} - {summary}</a>.
      </p>
      <p>- Worklist.net</p>'
)
);
