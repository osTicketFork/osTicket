<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !$thisclient->isValid()) die('Access Denied');

$settings = &$_SESSION['client:Q'];

// Unpack search, filter, and sort requests
if (isset($_REQUEST['clear']))
    $settings = array();
if (isset($_REQUEST['keywords'])) {
    $settings['keywords'] = $_REQUEST['keywords'];
}
if (isset($_REQUEST['topic_id'])) {
    $settings['topic_id'] = $_REQUEST['topic_id'];
}
if (isset($_REQUEST['status'])) {
    $settings['status'] = $_REQUEST['status'];
}
if (isset($_REQUEST['my'])) 
    $settings['my'] = $_REQUEST['my'];

$org_tickets = $thisclient->canSeeOrgTickets();
if ($settings['keywords']) {
    // Don't show stat counts for searches
    $openTickets = $closedTickets = -1;
}
elseif ($settings['topic_id']) {
    $openTickets = $thisclient->getNumTopicTicketsInState($settings['topic_id'],
        'open', $org_tickets);
    $closedTickets = $thisclient->getNumTopicTicketsInState($settings['topic_id'],
        'closed', $org_tickets);
}
else {
    $openTickets = $thisclient->getNumOpenTickets($org_tickets);
    $closedTickets = $thisclient->getNumClosedTickets($org_tickets);
}

$tickets = Ticket::objects();

$qs = array();
$status=null;

$mine = $settings['my'];
if (!isset($mine)) {
    $mine=1;
};
 
if ($settings['status'])
    $status = strtolower($settings['status']);
    switch ($status) {
    default:
        $status = 'open';
    case 'open':
    case 'closed':
        $results_type = ($status == 'closed') ? __('All Closed Tickets') : __('All Open Tickets');
        $tickets->filter(array('status__state' => $status));
        break;
}


if ($mine != 0){
    $results_type = ($settings['my'] != 0 && $status == 'closed') ? __('My Closed Tickets') : __('My Open Tickets');
// Add visibility constraints
$tickets->filter(Q::any(array(
    'user_id' => $thisclient->getId(),
    'thread__collaborators__user_id' => $thisclient->getId(),
)));
}

$sortOptions=array('id'=>'number', 'subject'=>'cdata__subject',
                    'status'=>'status__name', 'dept'=>'dept__name','date'=>'created');
$orderWays=array('DESC'=>'-','ASC'=>'');
//Sorting options...
$order_by=$order=null;
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'date';
if($sort && $sortOptions[$sort])
    $order_by =$sortOptions[$sort];

$order_by=$order_by ?: $sortOptions['date'];
if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order=$orderWays[strtoupper($_REQUEST['order'])];

$x=$sort.'_sort';
$$x=' class="'.strtolower($_REQUEST['order'] ?: 'desc').'" ';

$basic_filter = Ticket::objects();
if ($settings['topic_id']) {
    $basic_filter = $basic_filter->filter(array('topic_id' => $settings['topic_id']));
}

if ($settings['status'])
    $status = strtolower($settings['status']);
    switch ($status) {
    default:
        $status = 'open';
    case 'open':
    case 'closed':
        $results_type = ($status == 'closed') ? __('Closed Tickets') : __('Open Tickets');
        $basic_filter->filter(array('status__state' => $status));
        break;
}

// Add visibility constraints — use a union query to use multiple indexes,
// use UNION without "ALL" (false as second parameter to union()) to imply
// unique values
$visibility = $basic_filter->copy()
    ->values_flat('ticket_id')
    ->filter(array('user_id' => $thisclient->getId()))
    ->union($basic_filter->copy()
        ->values_flat('ticket_id')
        ->filter(array('thread__collaborators__user_id' => $thisclient->getId()))
    , false);

if ($thisclient->canSeeOrgTickets()) {
    $visibility = $visibility->union(
        $basic_filter->copy()->values_flat('ticket_id')
            ->filter(array('user__org_id' => $thisclient->getOrgId()))
    , false);
}

// Perform basic search
if ($settings['keywords']) {
    $q = trim($settings['keywords']);
    if (is_numeric($q)) {
        $tickets->filter(array('number__startswith'=>$q));
    } elseif (strlen($q) > 2) { //Deep search!
        // Use the search engine to perform the search
        $tickets = $ost->searcher->find($q, $tickets);
    }
}

$tickets->distinct('ticket_id');

TicketForm::ensureDynamicDataView();

$total=$tickets->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('tickets.php', $qs);
$tickets->filter(array('ticket_id__in' => $visibility));
$pageNav->paginate($tickets);

$showing =$total ? $pageNav->showing() : "";
if(!$results_type)
{
    $results_type=ucfirst($status).' '.__('Tickets');
}
$showing.=($status)?(' '.$results_type):' '.__('All Tickets');
if($search)
    $showing=__('Search Results').": $showing";

$negorder=$order=='-'?'ASC':'DESC'; //Negate the sorting

$tickets->values(
    'ticket_id', 'number', 'created', 'isanswered', 'source', 'status_id',
    'status__state', 'status__name', 'cdata__subject', 'dept_id',
    'dept__name', 'dept__ispublic', 'user__default_email__address'
);

?>
<!-- tickets.inc -->
<div class="row">
<form action="tickets.php" method="get" id="ticketSearchForm">
  <div class="row">
    <div class="col-xs-12">
     <div class="input-group">
        <input type="hidden" name="a"  value="search">
        <input type="search" class="form-control" name="keywords" size="30" value="<?php echo Format::htmlchars($settings['keywords']); ?>">
        <span class="input-group-btn">
        <select name="topic_id" class="nowarn btn" onchange="javascript: this.form.submit(); ">

            <option value="">&mdash; <?php echo __('All Topics');?> &mdash;</option>

<?php       foreach (Topic::getHelpTopics(true) as $id=>$name) {
                $count = $thisclient->getNumTopicTickets($id);
                //Show all topics (teams)
                //if ($count == 0)
                //    continue;
?>
                <option value="<?php echo $id; ?>"
                    <?php if ($settings['topic_id'] == $id) echo 'selected="selected"'; ?>
                    ><?php echo sprintf('%s (%d)', Format::htmlchars($name),
                    $thisclient->getNumTopicTickets($id)); ?></option>
<?php       } ?>
        </select>
        </span>
        <span class="input-group-btn">
            <input type="submit" class="btn btn-default" value="<?php echo __('Search');?>">
        </span>
      </div>
   </div>
</form>
</row>
<?php if ($settings['keywords'] || $settings['topic_id'] || $_REQUEST['sort']) { ?>
<div style="margin-top:10px"><strong><a href="?clear" style="color:#777"><i class="icon-remove-circle"></i> <?php echo __('Clear all filters and sort'); ?></a></strong></div>
<?php } ?>
<div class="clearfix"></div>
</div>
<div class="row">
<div class="display-table">
    <div class="col-xs-6 display-cell-bottom">
        <h2>
            <a href="<?php echo Format::htmlchars($_SERVER['REQUEST_URI']); ?>"
                ><i class="refresh icon-refresh"></i>
            <?php echo __('Tickets'); ?>
            </a>
        </h2>
    </div>
    <div class="col-xs-6 display-cell-bottom">
	    <div class="list-group text-right" style="margin-top: 20px; margin-bottom: 10px;">
                    <a class="state <?php if ($mine != 0  && $status == 'open') echo 'active'; ?>"
                        href="?<?php echo Http::build_query(array('a' => 'search', 'status' => 'open', 'my' => '1')); ?>">
                        <small><span class="glyphicon glyphicon-tag white"></span><?php
                        echo sprintf('%s <span class="badge">%d</span>', str_replace(" ", "&nbsp;", _P('ticket-status', 'My Open')), $thisclient->getNumOpenTickets());
                        ?></small></a>
                    <a class="state <?php if ($mine != 0  && $status == 'closed') echo 'active'; ?>"
                        href="?<?php echo Http::build_query(array('a' => 'search', 'status' => 'closed', 'my' => '1')); ?>">
                        <small><span class="glyphicon glyphicon-tag"></span><?php
                        echo sprintf('%s <span class="badge">%d</span>', str_replace(" ", "&nbsp;", __('My Closed')), $thisclient->getNumClosedTickets());
                        ?></small></a>
                    <a class="state <?php if ( $status == 'open' && $mine != 1) echo 'active'; ?>"
                        href="?<?php echo Http::build_query(array('a' => 'search', 'status' => 'open', 'my' => '0')); ?>">
                        <small><span class="glyphicon glyphicon-tags white"></span><?php
                        echo sprintf('%s', str_replace(" ", "&nbsp;", _P('ticket-status', 'All Open')), $thisclient->getNumOpenTickets());
                        ?></small></a>
                    <a class="state <?php if ($status == 'closed' && $mine != 1 ) echo 'active'; ?>"
                        href="?<?php echo Http::build_query(array('a' => 'search', 'status' => 'closed', 'my' => '0')); ?>">
                        <small><span class="glyphicon glyphicon-tags"></span><?php
                        echo sprintf('%s', str_replace(" ", "&nbsp;", __('All Closed')), $thisclient->getNumClosedTickets());
                        ?></small></a>
                </div>
    </div>
</div>
</div>
<div class="table-responsive">
<table id="ticketTable" class="table table-striped table-hover table-condensed">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th class="text-nowrap">
                <a href="tickets.php?sort=ID&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Ticket ID"><?php echo __('Ticket #');?></a>
            </th>
            <th class="text-nowrap">
                <a href="tickets.php?sort=date&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Date"><?php echo __('Create Date');?></a>
            </th>
            <th>
                <a href="tickets.php?sort=status&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Status"><?php echo __('Status');?></a>
            </th>
            <th>
                <a href="tickets.php?sort=subj&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Title"><?php echo __('Title');?></a>
            </th>
            <th class="hidden-xs">
                <a href="tickets.php?sort=dept&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By AI Team"><?php echo __('AI Team');?></a>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php
     $subject_field = TicketForm::objects()->one()->getField('subject');
     $defaultDept=Dept::getDefaultDeptName(); //Default public dept.
     if ($tickets->exists(true)) {
         foreach ($tickets as $T) {
            $dept = $T['dept__ispublic']
                ? Dept::getLocalById($T['dept_id'], 'name', $T['dept__name'])
                : $defaultDept;
            $subject = $subject_field->display(
                $subject_field->to_php($T['cdata__subject']) ?: $T['cdata__subject']
            );
            $status = TicketStatus::getLocalById($T['status_id'], 'value', $T['status__name']);
            if (false) // XXX: Reimplement attachment count support
                $subject.='  &nbsp;&nbsp;<span class="Icon file"></span>';

            $ticketNumber=$T['number'];
            if($T['isanswered'] && !strcasecmp($T['status__state'], 'open')) {
                $subject="<b>$subject</b>";
                $ticketNumber="<b>$ticketNumber</b>";
            }
            ?>
            <tr id="<?php echo $T['ticket_id']; ?>">
                <td class="text-nowrap">
                <a class="Icon <?php echo strtolower($T['source']); ?>Ticket" title="<?php echo $T['user__default_email__address']; ?>"
                    href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $ticketNumber; ?></a>
                </td>
                <td class="text-nowrap">&nbsp;<?php echo Format::date($T['created']); ?></td>
                <td>&nbsp;<?php echo $status; ?></td>
                <td>
                    <div style="max-height: 1.2em; max-width: 320px;" class="link truncate" href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $subject; ?></div>
                </td>
                <td class="hidden-xs">&nbsp;<span class="truncate"><?php echo $dept; ?></span></td>
            </tr>
        <?php
        }

     } else {
         echo '<tr><td colspan="5">'.__('Your query did not match any records').'</td></tr>';
     }
    ?>
    </tbody>
</table>
</div>
<?php
if ($total) {
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
}
?>
