<?php

	/**
	 * <p>This control is used to display a simple html table.
	 *
	 * <p>The control itself will display things based off of an array of objects that gets set as the "Data Source".
	 * It is particularly useful when combined with the Class::LoadArrayByXXX() functions or the Class::LoadAll()
	 * that is generated by the CodeGen framework, or when combined with custom Class ArrayLoaders that you define
	 * youself, but who's structure is based off of the CodeGen framework.</p>
	 *
	 * <p>For each item in a datasource's Array, a row (&lt;tr&gt;) will be generated.
	 * You can define any number of QSimpleTableColumns which will result in a &lt;td&gt; for each row.
	 * Using the QSimpleTableColumn's Accessor property, you can specify how the data for each cell should be
	 * fetched from the datasource.</p>
	 *
	 * <p><i>NOTE</i>: Unlike QDataGrid, this class does not use eval() for evaluating the cell values. Instead, a variety of
	 * methods can be used to fetch the data for cells, including callable objects.</p>
	 *
	 * @package Controls
	 *
	 * @property string $RowCssClass class to be given to the row tag
	 * @property string $AlternateRowCssClass class to be given to each alternate row tag
	 * @property string $HeaderRowCssClass class to be given the header row
	 * @property boolean $ShowHeader true to show the header row
	 * @property boolean $ShowFooter true to show the footer row
	 * @property boolean $RenderColumnTags true to include col tags in the table output
	 * @property boolean $HideIfEmpty true to completely hide the table if there is no data, vs. drawing the table with no rows.
	 * @throws QCallerException
	 *
	 */
	abstract class QSimpleTableBase extends QPaginatedControl {
		/** @var QAbstractSimpleTableColumn[] */
		protected $objColumnArray;

		protected $strRowCssClass = null;
		protected $strAlternateRowCssClass = null;
		protected $strHeaderRowCssClass = null;
		protected $blnShowHeader = true;
		protected $blnShowFooter = false;
		protected $blnRenderColumnTags = false;
		protected $strCaption = null;
		protected $blnHideIfEmpty = false;

		/** @var integer */
		protected $intHeaderRowCount = 1;
		/** @var  integer Used during rendering to report which header row is being drawn in a multi-row header. */
		protected $intCurrentHeaderRowIndex;

		public function __construct($objParentObject, $strControlId = null)	{
			try {
				parent::__construct($objParentObject, $strControlId);
			} catch (QCallerException  $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
		}

		public function ParsePostData() { }

		/**
		 * Add an Index column and return it.
		 * Enter description here ...
		 * @param string $strName column name
		 * @param mixed $mixIndex the index to use to access the cell date. i.e. $item[$index]
		 * @param integer $intColumnIndex column position
		 */
		public function CreateIndexedColumn($strName = '', $mixIndex = null, $intColumnIndex = -1) {
			if (is_null($mixIndex)) {
				$mixIndex = count($this->objColumnArray);
			}
			$objColumn = new QSimpleTableIndexedColumn($strName, $mixIndex);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a property column and return it. The assumption is that each row's data is an object.
		 * 
		 * @param string $strName name of column
		 * @param string $strProperty property to use to get the cell data. i.e. $item->$property
		 * @param integer $intColumnIndex column position
		 * @param object $objBaseNode a query node from which the property descends, if you are using the sorting capabilities
		 */
		public function CreatePropertyColumn($strName, $strProperty, $intColumnIndex = -1, $objBaseNode = null) {
			$objColumn = new QSimpleTablePropertyColumn($strName, $strProperty, $objBaseNode);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a callable column and return it.
		 * 
		 * @param string $strName column name
		 * @param object $objCallable a callable object. Note that this can be an array.
		 * @param integer $intColumnIndex column position
		 */
		public function CreateCallableColumn($strName, $objCallable, $intColumnIndex = -1) {
			$objColumn = new QSimpleTableCallableColumn($strName, $objCallable);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a virtual attribute column.
		 *
		 * @param $strName
		 * @param $strAttribute
		 * @param $intColumnIndex
		 * @return QVirtualAttributeColumn
		 */
		public function CreateVirtualAttributeColumn ($strName, $strAttribute, $intColumnIndex = -1) {
			$objColumn = new QVirtualAttributeColumn($strName, $strAttribute);
			$this->AddColumnAt($intColumnIndex, $objColumn);
			return $objColumn;
		}

		/**
		 * Add a column to the end of the column array.
		 * @param QAbstractSimpleTableColumn $objColumn
		 * @return QAbstractSimpleTableColumn
		 */
		public function AddColumn(QAbstractSimpleTableColumn $objColumn) {
			$this->blnModified = true;
			$this->objColumnArray[] = $objColumn;
			$objColumn->_ParentTable = $this;
			return $objColumn;
		}

		/**
		 * Move the named column to the given position
		 * @param string $strName column name
		 * @param integer $intColumnIndex new position
		 * @param string $strNewName new column name
		 */
		public function MoveColumn($strName, $intColumnIndex = -1, $strNewName = null) {
			$col = $this->RemoveColumnByName($strName);
			$this->AddColumnAt($intColumnIndex, $col);
			if ($strNewName !== null) {
				$col->Name = $strNewName;
			}
			return $col;
		}

		/**
		 * Rename a named column
		 * 
		 * @param string $strOldName
		 * @param string $strNewName
		 */
		public function RenameColumn($strOldName, $strNewName) {
			$col = $this->GetColumnByName($strOldName);
			$col->Name = $strNewName;
			return $col;
		}

		/**
		 * Add a column at the given position
		 * 
		 * @param integer $intColumnIndex column position
		 * @param QAbstractSimpleTableColumn $objColumn
		 * @throws QInvalidCastException
		 */
		public function AddColumnAt($intColumnIndex, QAbstractSimpleTableColumn $objColumn) {
			try {
				$intColumnIndex = QType::Cast($intColumnIndex, QType::Integer);
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			$this->blnModified = true;
			if ($intColumnIndex < 0 || $intColumnIndex > count($this->objColumnArray)) {
				$this->AddColumn($objColumn);
				return;
			}

			if ($intColumnIndex == 0) {
				$this->objColumnArray = array_merge(array($objColumn), $this->objColumnArray);
			} else {
				$this->objColumnArray = array_merge(array_slice($this->objColumnArray, 0, $intColumnIndex),
													array($objColumn),
													array_slice($this->objColumnArray, $intColumnIndex));
			}
		}

		/**
		 * @param int $intColumnIndex 0-based index of the column to remove
		 * @return QAbstractSimpleTableColumn the removed column
		 * @throws QIndexOutOfRangeException|QInvalidCastException
		 */
		public function RemoveColumn($intColumnIndex) {
			$this->blnModified = true;
			try {
				$intColumnIndex = QType::Cast($intColumnIndex, QType::Integer);
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			if ($intColumnIndex < 0 || $intColumnIndex > count($this->objColumnArray)) {
				throw new QIndexOutOfRangeException($intColumnIndex, "RemoveColumn()");
			}

			$col = $this->objColumnArray[$intColumnIndex];
			array_splice($this->objColumnArray, $intColumnIndex, 1);
			return $col;
		}

		/**
		 * Remove the first column that has the given name
		 * @param string $strName name of the column to remove
		 * @return QAbstractSimpleTableColumn the removed column or null of no column with the given name was found
		 */
		public function RemoveColumnByName($strName) {
			$this->blnModified = true;
			for ($intIndex = 0; $intIndex < count($this->objColumnArray); $intIndex++) {
				if ($this->objColumnArray[$intIndex]->Name == $strName) {
					$col = $this->objColumnArray[$intIndex];
					array_splice($this->objColumnArray, $intIndex, 1);
					return $col;
				}
			}
			return null;
		}

		/**
		 * Remove all the columns that have the given name
		 * @param string $strName name of the columns to remove
		 * @return QAbstractSimpleTableColumn[] the array of columns removed
		 */
		public function RemoveColumnsByName($strName/*...*/) {
			return $this->RemoveColumns(func_get_args());
		}

		/**
		 * Remove all the columns that have any of the names in $strNamesArray
		 * @param string[] $strNamesArray names of the columns to remove
		 * @return QAbstractSimpleTableColumn[] the array of columns removed
		 */
		public function RemoveColumns($strNamesArray) {
			$this->blnModified = true;
			$kept = array();
			$removed = array();
			foreach ($this->objColumnArray as $objColumn) {
				if (array_search($objColumn->Name, $strNamesArray) === false) {
					$kept[] = $objColumn;
				} else {
					$removed[] = $objColumn;
				}
			}
			$this->objColumnArray = $kept;
			return $removed;
		}

		public function RemoveAllColumns() {
			$this->blnModified = true;
			$this->objColumnArray = array();
		}

		/**
		 * @return QAbstractSimpleTableColumn[]
		 */
		public function GetAllColumns() {
			return $this->objColumnArray;
		}

		/**
		 * Get the column at the given index, or null if the index is not valid
		 * @param $intColumnIndex
		 * @return QAbstractSimpleTableColumn
		 */
		public function GetColumn($intColumnIndex) {
			if (array_key_exists($intColumnIndex, $this->objColumnArray))
				return $this->objColumnArray[$intColumnIndex];
			return null;
		}

		/**
		 * Get the first column that has the given name, or null if a column with the given name does not exist
		 * @param string $strName column name
		 * @return QAbstractSimpleTableColumn
		 */
		public function GetColumnByName($strName) {
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Name == $strName)
					return $objColumn;
			return null;
		}

		/**
		 * Get the first column that has the given name, or null if a column with the given name does not exist
		 * @param string $strName column name
		 * @return QAbstractSimpleTableColumn
		 */
		public function GetColumnIndex($strName) {
			$intIndex = -1;
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn) {
				++$intIndex;
				if ($objColumn->Name == $strName)
					return $intIndex;
			}
			return $intIndex;
		}

		/**
		 * Get all the columns that have the given name
		 * @param string $strName column name
		 * @return QAbstractSimpleTableColumn[]
		 */
		public function GetColumnsByName($strName) {
			$objColumnArrayToReturn = array();
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Name == $strName)
					array_push($objColumnArrayToReturn, $objColumn);
			return $objColumnArrayToReturn;
		}

		/**
		 * 
		 * Returns the HTML for the header row, including the <<tr>> and <</tr>> tags
		 */
		protected function GetHeaderRowHtml() {
			$strToReturn = '';
			for ($i = 0; $i < $this->intHeaderRowCount; $i++) {
				$this->intCurrentHeaderRowIndex = $i;
				$strToReturn .= '<tr';
				$strParamArray = $this->GetHeaderRowParams();
				foreach ($strParamArray as $key=>$str) {
					$strToReturn .= ' ' . $key . '="' . $str . '"';
				}
				$strToReturn .= '>';

				if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn) {
					$strToReturn .= $objColumn->RenderHeaderCell();
				}
				$strToReturn .= "  </tr>\r\n";
			}

			return $strToReturn;
		}

		/**
		 * Returns a key=>val array of parameters to insert inside of the header row's <<tr>> tag.
		 *
		 * @return array
		 */
		protected function GetHeaderRowParams () {
			$strParamArray = array();
			if ($strClass = $this->strHeaderRowCssClass) {
				$strParamArray['class'] = $strClass;
			}
			return $strParamArray;		
		}
		
		
		/**
		 * 
		 * Get the html for the row, from the opening <<tr>> to the closing <</tr>> inclusive
		 * @param object $objObject Current object from the DataSource array
		 * @param integer $intCurrentRowIndex Current visual row index being output. 
		 *  This is NOT the index of the data source, only the visual row number currently on screen.
		 */
		protected function GetDataGridRowHtml($objObject, $intCurrentRowIndex) {
			$strToReturn = '<tr';
			
			$strParamArray = $this->GetRowParams($objObject, $intCurrentRowIndex);
			foreach ($strParamArray as $key=>$str) {
				$strToReturn .= ' ' . $key . '="' . $str . '"';
			}
			
			$strToReturn .= '>';

			foreach ($this->objColumnArray as $objColumn) {
				try {
					$strToReturn .= $objColumn->RenderCell($objObject);
				} catch (QCallerException $objExc) {
					$objExc->IncrementOffset();
					throw $objExc;
				}
			}
			$strToReturn .= "</tr>\r\n";
			return $strToReturn;
		}
		
		/**
		 * Returns a key/val array of params that will be inserted inside the <<tr>> tag for this row. 
		 * 
		 * Handles  class, style, and id by default. Override to add additional types of parameters,
		 * like an 'onclick' paramater for example. No checking is done on these params, the raw strings are output.
		 * 
		 * @param mixValue $item
		 */
		protected function GetRowParams ($objObject, $intCurrentRowIndex) {
			$strParamArray = array();
			if ($strClass = $this->GetRowClass ($objObject, $intCurrentRowIndex)) {
				$strParamArray['class'] = $strClass;
			}
			
			if ($strId = $this->GetRowId ($objObject, $intCurrentRowIndex)) {
				$strParamArray['id'] = addslashes($strId);
			}
			
			if ($strStyle = $this->GetRowStyle ($objObject, $intCurrentRowIndex)) {
				$strParamArray['style'] = $strStyle;
			}
			return $strParamArray;		
		}
		
		
		/**
		 * 
		 * Return the html row id.
		 * 
		 * Override this to give the row an id.
		 * 
		 * @param object $objObject	object associated with this row
		 * @param integer $intRowIndex  index of the row
		 */
		protected function GetRowId ($objObject, $intRowIndex) {
			return null;
		}

		/**
		 * 
		 * Return the style string for this row.
		 * 
		 * @param object $objObject
		 * @param integer $intRowIndex
		 */
		protected function GetRowStyle ($objObject, $intRowIndex) {
			return null;
		}
		
		/**
		 * 
		 * Return the class string of this row.
		 * 
		 * @param object $objObject
		 * @param integer $intRowIndex
		 */
		protected function GetRowClass ($objObject, $intRowIndex) {
			if (($intRowIndex % 2) == 1 && $this->strAlternateRowCssClass) {
				return $this->strAlternateRowCssClass;
			} else if ($this->strRowCssClass) {
				return $this->strRowCssClass;
			}
			else {
				return null;
			}
		}
		
		/**
		 * Override to return the footer row html
		 */
		protected function GetFooterRowHtml() { }
		
		/**
		 * Returns column tags. Only called if blnRenderColumnTags is true.
		 * @return string Column tag html
		 */
		protected function GetColumnTagsHtml() {
			$strToReturn = '';
			$len = count($this->objColumnArray);
			$i = 0;
			while ($i < $len) {
				$objColumn = $this->objColumnArray[$i];
				$strToReturn .= $objColumn->RenderColTag();
				$i += $objColumn->Span;
			}
			$strToReturn .= "\n";
			return $strToReturn;
		}

		protected function GetControlHtml() {
			$this->DataBind();

			if (empty ($this->objDataSource) && $this->blnHideIfEmpty) {
				$this->objDataSource = null;
				return '';
			}

			// Table Tag
			$strStyle = $this->GetStyleAttributes();
			if ($strStyle)
				$strStyle = sprintf('style="%s" ', $strStyle);
			$strToReturn = sprintf("<table id=\"%s\" %s%s>\n", $this->strControlId, $this->GetAttributes(), $strStyle);

			// Caption if present
			if ($this->strCaption) {
				$strToReturn .= "<caption>" . QApplication::HtmlEntities($this->strCaption) . "</caption>\n";
			}
			
			// Column tags (if applicable)
			if ($this->blnRenderColumnTags) {
				$strToReturn .= $this->GetColumnTagsHtml();
			}
			
			// Header Row (if applicable)
			if ($this->blnShowHeader)
				$strToReturn .= "<thead>\n" . $this->GetHeaderRowHtml() . "</thead>\n";

			// Footer Row (if applicable)
			if ($this->blnShowFooter)
				$strToReturn .= "<tfoot>\n" . $this->GetFooterRowHtml() . "</tfoot>\n";

			// DataGrid Rows
			$strToReturn .= "<tbody>\n";
			$intCurrentRowIndex = 0;
			if ($this->objDataSource) {
				foreach ($this->objDataSource as $objObject) {
					$strToReturn .= $this->GetDataGridRowHtml($objObject, $intCurrentRowIndex);
					$intCurrentRowIndex++;
				}
			}
			$strToReturn .= "</tbody>\r\n";

			// Finish Up
			$strToReturn .= '</table>';
			$this->objDataSource = null;
			return $strToReturn;
		}

		/**
		 * Preserialize the columns, since some columns might have references to the form.
		 */
		public function Sleep() {
			foreach ($this->objColumnArray as $objColumn) {
				$objColumn->Sleep();
			}
			parent::Sleep();
		}

		/**
		 * Restore references.
		 *
		 * @param QForm $objForm
		 */
		public function Wakeup(QForm $objForm) {
			parent::Wakeup($objForm);
			foreach ($this->objColumnArray as $objColumn) {
				$objColumn->Wakeup($objForm);
			}
		}


		public function __get($strName) {
			switch ($strName) {
				case 'RowCssClass':
					return $this->strRowCssClass;
				case 'AlternateRowCssClass':
					return $this->strAlternateRowCssClass;
				case 'HeaderRowCssClass':
					return $this->strHeaderRowCssClass;
				case 'ShowHeader':
					return $this->blnShowHeader;
				case 'ShowFooter':
					return $this->blnShowFooter;
				case 'RenderColumnTags':
					return $this->blnRenderColumnTags;
				case 'Caption':
					return $this->strCaption;
				case 'HeaderRowCount':
					return $this->intHeaderRowCount;
				case 'CurrentHeaderRowIndex':
					return $this->intCurrentHeaderRowIndex;
				case 'HideIfEmpty':
					return $this->blnHideIfEmpty;

				default:
					try {
						return parent::__get($strName);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		public function __set($strName, $mixValue) {
			switch ($strName) {
				case "RowCssClass":
					try {
						$this->strRowCssClass = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "AlternateRowCssClass":
					try {
						$this->strAlternateRowCssClass = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "HeaderRowCssClass":
					try {
						$this->strHeaderRowCssClass = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "ShowHeader":
					try {
						$this->blnShowHeader = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "ShowFooter":
					try {
						$this->blnShowFooter = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "RenderColumnTags":
					try {
						$this->blnRenderColumnTags = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "Caption":
					try {
						$this->strCaption = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "HeaderRowCount":
					try {
						$this->intHeaderRowCount = QType::Cast($mixValue, QType::Integer);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "HideIfEmpty":
					try {
						$this->blnHideIfEmpty = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}


				default:
					try {
						parent::__set($strName, $mixValue);
						break;
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

	}

?>