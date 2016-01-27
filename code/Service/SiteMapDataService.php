<?php

/**
 * Capture / cache some commonly used data elements for each page
 * 
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 */
class SiteMapDataService
{
    
    protected $items = array();
    
    protected $mapped = array();

    public $processed = array();

    public $allPages = array();

    /**
     *
     * @var string
     */
    public $itemClass = 'SiteMapMenuItem';
    
    /**
     * Additional fields to be queried from the SiteTree/Page tables
     *
     * @var array
     */
    public $additionalFields = array();
    
    /**
     * Needed to create the menu item objects
     * 
     * @var Injector
     */
    public $injector;
    
    public function getItem($id)
    {
        $this->getItems();
        return isset($this->items[$id]) ? $this->items[$id] : null;
    }
    
    public function getItems()
    {
        if (!$this->items) {
            $this->generateMenuItems();
        }
        
        return $this->items;
    }
    
    public function generateMenuItems()
    {
        $all = array();
        $allids = array();
        
        $public = $this->getPublicNodes();
        foreach ($public as $row) {
            if (!isset($allids[$row['ID']])) {
                $allids[$row['ID']] = true;
                $all[] = $row;
            }
        }
        
        // and private nodes
        $private = $this->getPrivateNodes();
        foreach ($private as $row) {
            if (!isset($allids[$row['ID']])) {
                $allids[$row['ID']] = true;
                $all[] = $row;
            }
        }
        
        $others = $this->getAdditionalNodes();
        foreach ($others as $row) {
            if (!isset($allids[$row['ID']])) {
                $allids[$row['ID']] = true;
                $all[] = $row;
            }
        }

        $deferred = array();
        $final = array();
        $hierarchy = array();
        
        $counter = 0;
        
        $this->processRows($final, $all, $allids, 0);
        
        $ordered = ArrayList::create();
        // start at 0
        if (isset($final[0]['kids'])) {
            foreach ($final[0]['kids'] as $id) {
                $node = $final[$id];
                $this->buildLinks($node, null, $ordered, $final);
            }
        }
    }

    public function getSitemapPages()
    {
        $all = array();
        $allids = array();
        $final = array();
        
        $public = $this->getNodes();
        foreach ($public as $row) {
            if (!isset($allids[$row['ID']])) {
                $allids[$row['ID']] = true;
                $all[] = $row;
            }
        }
        
        $this->processRows($final, $all, $allids, 0);
        foreach ($final as $id => $page) {
            if (!isset($page[$id]) && $id > 0) {
                $this->allPages[$id] = $page;
            }
        }
        return new ArrayList($final);
    }

    protected function processPageToXML($xml, $page)
    {
        if (isset($page['ID']) && !isset($this->processed[$page['ID']])) {
            // add stuff
            $child = $xml->addChild('url');
            $child->addChild('loc', $page['Title']);
            $child->addChild('changefreq', $page['ChangeFreq']);
            $child->addChild('priority', $page['Priority']);
            // add page id to processed
            $this->processed[$page['ID']] = true;
        }
        return $xml;
    }

    protected function queryFields()
    {
        $fields = array(
            '"SiteTree"."ID" AS ID',
            'ClassName',
            'Title',
            'MenuTitle',
            'URLSegment',
            'ParentID',
            'CanViewType',
            'Sort',
            'ShowInMenus',
            'ChangeFreq',
            'Priority'
        );
        
        foreach ($this->additionalFields as $field) {
            $fields[] = $field;
        }
        return $fields;
    }
    
    public function getNodes()
    {
        $fields = $this->queryFields();

        $query = new SQLQuery($fields, '"SiteTree"');
        $query = $query->setOrderBy('Sort', 'ASC');
        
        $this->adjustForVersioned($query);
        $results = $query->execute();
        return $results;
    }

    protected function getPublicNodes()
    {
        $fields = $this->queryFields();

        $query = new SQLQuery($fields, '"SiteTree"');
        //$query = $query->addInnerJoin('Page', '"SiteTree"."ID" = "Page"."ID"');
        $query = $query->setOrderBy('Sort', 'ASC');
        
        // if the user is logged in, we only exclude nodes that have a specific permission set on them
        if (Member::currentUserID()) {
            $query->addWhere('"CanViewType" NOT IN (\'OnlyTheseUsers\')');
        } else {
            $query->addWhere('"CanViewType" NOT IN (\'LoggedInUsers\', \'OnlyTheseUsers\')');
        }
        
        $this->adjustForVersioned($query);
        $results = $query->execute();
        return $results;
    }
    
    /**
     * Get private nodes, assuming SilverStripe's default perm structure
     * @return SS_Query
     */
    protected function getPrivateNodes()
    {
        if (!Member::currentUserID()) {
            return array();
        }
        $groups = Member::currentUser()->Groups()->column();
        
        if (!count($groups)) {
            return $groups;
        }
        
        $fields = $this->queryFields();

        $query = new SQLQuery($fields, '"SiteTree"');
        //$query = $query->addInnerJoin('Page', '"SiteTree"."ID" = "Page"."ID"');
        $query = $query->setOrderBy('ParentID', 'ASC');

        $query->addWhere('"CanViewType" IN (\'OnlyTheseUsers\')');

        if (Permission::check('ADMIN')) {
            // don't need to restrict the canView by anything
        } else {
            $query->addInnerJoin('SiteTree_ViewerGroups', '"SiteTree_ViewerGroups"."SiteTreeID" = "SiteTree"."ID"');
            $query->addWhere('"SiteTree_ViewerGroups"."GroupID" IN (' . implode(',', $groups).')');
        }
        
        $this->adjustPrivateNodeQuery($query);
        $this->adjustForVersioned($query);
        $sql = $query->sql();
        
        $results = $query->execute();
        return $results;
    }

    protected function adjustForVersioned(SQLQuery $query)
    {
        $ownerClass = 'Page';
        $stage = Versioned::current_stage();
        if ($stage && ($stage != 'Stage')) {
            foreach ($query->getFrom() as $table => $dummy) {
                // Only rewrite table names that are actually part of the subclass tree
                // This helps prevent rewriting of other tables that get joined in, in
                // particular, many_many tables
                if (class_exists($table) && ($table == $ownerClass
                        || is_subclass_of($table, $ownerClass)
                        || is_subclass_of($ownerClass, $table))) {
                    $query->renameTable($table, $table . '_' . $stage);
                }
            }
        }
    }
	
	protected function adjustPrivateNodeQuery(SQLQuery $query) {
		
	}
	
	protected function getAdditionalNodes() {
		return array();
	}
    
    protected function buildLinks($node, $parent, $out, $nodemap)
    {
        $kids = isset($node['kids']) ? $node['kids'] : array();
        $node = $this->createMenuNode($node);
        $out->push($node);

        $node->Link = ltrim($parent
            ? ($parent->Link == '' ? 'home' : $parent->Link) . '/' . $node->URLSegment
            : $node->URLSegment, '/');
		
		if ($node->Link == 'home') {
			$node->Link = '';
		}
        
        foreach ($kids as $id) {
            $n = $nodemap[$id];
            $this->buildLinks($n, $node, $out, $nodemap);
        }
    }
    
    /**
     * Creates a menu item from an array of data
     * 
     * @param array $data
     * @returns SiteMapMenuItem
     */
    public function createMenuNode($data)
    {
        $cls = $this->itemClass;
        $node = $cls::create($data, $this);
        $this->items[$node->ID] = $node;
        return $node;
    }

    public function processRows(&$final, $remaining, $ids, $lastcount)
    {
        $deferred = array();
        foreach ($remaining as $row) {
            // orphan
            if ($row['ParentID'] && !isset($ids[$row['ParentID']])) {
                continue;
            }
            if (!isset($final[$row['ID']])) {
                $final[$row['ID']] = $row;
            }
            if ($row['ParentID'] && !isset($final[$row['ParentID']])) {
                $deferred[$row['ID']] = $row;
            } else {
                
                // add to the hierarchy of things
                $existing = isset($final[$row['ParentID']]['kids'])
                    ? $final[$row['ParentID']]['kids']
                    : array();
                $existing[] = $row['ID'];
                $final[$row['ParentID']]['kids'] = $existing;
            }
        }
        
        if (count($deferred) == $lastcount) {
            return;
        }
        
        $lastcount = count($deferred);
        if (count($deferred)) {
            $this->processRows($final, $deferred, $ids, $lastcount);
        }
    }
}
