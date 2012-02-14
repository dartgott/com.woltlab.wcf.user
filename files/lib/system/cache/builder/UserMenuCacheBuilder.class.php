<?php
namespace wcf\system\cache\builder;
use wcf\data\user\menu\item\UserMenuItem;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Caches the user menu items tree.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.user
 * @subpackage	system.cache.builder
 * @category 	Community Framework
 */
class UserMenuCacheBuilder implements ICacheBuilder {
	protected $optionCategoryStructure = array();

	/**
	 * @see wcf\system\cache\ICacheBuilder::getData()
	 */
	public function getData(array $cacheResource) {
		list($cache, $packageID) = explode('-', $cacheResource['cache']); 
		$data = array();

		// get all menu items and filter menu items with low priority
		$sql = "SELECT		menuItem, menuItemID
			FROM		wcf".WCF_N."_user_menu_item menu_item
			LEFT JOIN	wcf".WCF_N."_package_dependency package_dependency
			ON 		(menu_item.packageID = package_dependency.dependency)
			WHERE		package_dependency.packageID = ?
			ORDER BY	package_dependency.priority ASC";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($packageID));
		$itemIDs = array();
		while ($row = $statement->fetchArray()) {
			$itemIDs[$row['menuItem']] = $row['menuItemID'];
		}
		
		if (count($itemIDs) > 0) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("menuItemID IN (?)", array($itemIDs));
			
			// get needed menu items and build item tree
			$sql = "SELECT		menu_item.packageID, menuItem, parentMenuItem,
						menuItemLink, permissions, options, packageDir
				FROM		wcf".WCF_N."_user_menu_item menu_item
				LEFT JOIN	wcf".WCF_N."_package package
				ON		(package.packageID = menu_item.packageID)
				".$conditions."
				ORDER BY	showOrder ASC";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while ($row = $statement->fetchArray()) {
				if (!isset($data[$row['parentMenuItem']])) {
					$data[$row['parentMenuItem']] = array();
				}
				
				$data[$row['parentMenuItem']][] = new UserMenuItem(null, $row);
			}
		}
		
		return $data;
	}
}