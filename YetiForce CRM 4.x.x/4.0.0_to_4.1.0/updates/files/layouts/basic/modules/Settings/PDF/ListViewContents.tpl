{*<!-- {[The file is published on the basis of YetiForce Public License 2.0 that can be found in the following directory: licenses/License.html or yetiforce.com]} --!>*}
{strip}
	<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
	<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
	<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

	<div class="listViewEntriesDiv overflowXAuto">
		{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
		<table class="table table-bordered table-condensed listViewEntriesTable">
			<thead>
				<tr class="listViewHeaders">
					<th width="1%" class="{$WIDTHTYPE}"></th>
						{assign var=WIDTH value={99/(count($LISTVIEW_HEADERS))}}
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
						<th width="{$WIDTH}%" nowrap {if $LISTVIEW_HEADER@last}colspan="2" {/if} class="{$WIDTHTYPE}">
							<a  {if !($LISTVIEW_HEADER->has('sort'))} class="listViewHeaderValues cursorPointer" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}" {/if}>{vtranslate($LISTVIEW_HEADER->get('label'), $QUALIFIED_MODULE)}
								{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}&nbsp;&nbsp;<span class="{$SORT_IMAGE}"></span>{/if}</a>
						</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
					<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}" 
						{if method_exists($LISTVIEW_ENTRY,'getDetailViewUrl')}data-recordurl="{$LISTVIEW_ENTRY->getDetailViewUrl()}"{/if}>
						<td width="1%" nowrap class="{$WIDTHTYPE}"></td>
						{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
							{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
							{assign var=LAST_COLUMN value=$LISTVIEW_HEADER@last}
							<td class="listViewEntryValue {$WIDTHTYPE}" data-name="{$LISTVIEW_HEADERNAME}" width="{$WIDTH}%" nowrap>
								&nbsp;{vtranslate($LISTVIEW_ENTRY->getDisplayValue($LISTVIEW_HEADERNAME), $QUALIFIED_MODULE)}
								{if $LAST_COLUMN && $LISTVIEW_ENTRY->getRecordLinks()}
								</td><td nowrap class="{$WIDTHTYPE}">
									<div class="pull-right actions">
										<span class="actionImages">
											{foreach item=RECORD_LINK from=$LISTVIEW_ENTRY->getRecordLinks()}
												{assign var="RECORD_LINK_URL" value=$RECORD_LINK->getUrl()}
												{if $RECORD_LINK->getLabel() eq 'LBL_DELETE_RECORD'}
													<span class="{$RECORD_LINK->getIcon()} alignMiddle templateDelete" title="{vtranslate($RECORD_LINK->getLabel(), $QUALIFIED_MODULE)}"></span>
												{elseif $RECORD_LINK->getLabel() eq 'LBL_EXPORT_RECORD'}
													<a href="{$RECORD_LINK_URL}"><span class="{$RECORD_LINK->getIcon()} alignMiddle"></span></a>
												{else}
													<a {if stripos($RECORD_LINK_URL, 'javascript:')===0} onclick="
														{$RECORD_LINK_URL|substr:strlen("javascript:")};
															if (event.stopPropagation){ldelim}
																event.stopPropagation();{rdelim} else{ldelim}
																event.cancelBubble = true;{rdelim}
														" {else} href='{$RECORD_LINK_URL}' {/if} class="{$RECORD_LINK->get('class')}">
														<span class="{$RECORD_LINK->getIcon()} alignMiddle" title="{vtranslate($RECORD_LINK->getLabel(), $QUALIFIED_MODULE)}"></span>
													</a>
												{/if}
												{if !$RECORD_LINK@last}
													&nbsp;&nbsp;
												{/if}
											{/foreach}
										</span>
									</div>
								</td>
							{/if}
							</td>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>

		<!--added this div for Temporarily -->
		{if $LISTVIEW_ENTRIES_COUNT eq '0'}
			<table class="emptyRecordsDiv">
				<tbody>
					<tr>
						<td>
							{vtranslate('LBL_NO_RECORDS_FOUND', $QUALIFIED_MODULE)}
						</td>
					</tr>
				</tbody>
			</table>
		{/if}
	</div>
{/strip}
