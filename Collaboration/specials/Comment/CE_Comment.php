<?php
/*  Copyright 2009, ontoprise GmbH
 *  This file is part of the Collaboration-Extension.
 *
 *   The Collaboration-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The Collaboration-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @file
 * @ingroup CEComment
 * 
 * This file contains the implementation of comment creation for Collaboration.
 *
 * @author Benjamin Langguth
 * Date: 07.12.2009
 */

/**
 * @defgroup CEComment
 * @ingroup Collaboration
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the Collaboration extension. It is not a valid entry point.\n" );
}


class CEComment {
	
	/* constants */
	const SUCCESS = 0;
	const COMMENT_ALREADY_EXISTS = 1;
	const PERMISSION_ERROR = 2;
	
	
	public static function createComment($pageName, $pageContent, $editMode = false) {
		global $wgUser, $cegEnableComment, $cegEnableCommentFor;
		
		$title = Title::newFromText($pageName);
		if($title->getNamespace() != CE_COMMENT_NS) {
			$title = Title::makeTitle(CE_COMMENT_NS, $title);
		}
		$article = new Article($title);
		
		# check if comments are enabled #
		if ( !isset($cegEnableComment) || !$cegEnableComment )
			return CECommentUtils::createXMLResponse(
				wfMsg('ce_cf_disabled'),
				self::PERMISSION_ERROR, $pageName);
		
		# check authorization #
		if ( !isset($cegEnableCommentFor)
			|| ($cegEnableCommentFor == CE_COMMENT_NOBODY )
			|| ( ($cegEnableCommentFor == CE_COMMENT_AUTH_ONLY) && !($wgUser->isAnon()) ) )
		{
			return CECommentUtils::createXMLResponse(
				wfMsg('ce_cf_disabled'),
				self::PERMISSION_ERROR, $pageName);
		} else {
			//user is allowed
			if ($article->exists() && !$editMode) {
				return CECommentUtils::createXMLResponse(
					wfMsg('ce_comment_exists', $pageName),
					self::COMMENT_ALREADY_EXISTS, $pageName);
			}
			if(!$title->userCan('edit')) {
				return CECommentUtils::createXMLResponse(
					wfMsg('ce_com_cannot_create'),
					self::PERMISSION_ERROR, $pageName);
			} else {
				
				// Insert current Date
				$date = new DateTime();
				$dateString = $date->format('c');
				if($editMode) {
					// use the original DATE!!!
					SMWQueryProcessor::processFunctionParams(array("[[".$pageName."]]", "?Has comment date=")
						,$querystring,$params,$printouts);
					$queryResult = explode("|",
						SMWQueryProcessor::getResultFromQueryString($querystring,$params,
						$printouts, SMW_OUTPUT_WIKI));
					//just get the first property value and use this
					if(isset($queryResult[0])) {
						// see '/extensions/SemanticMediaWiki/includes/SMW_DV_Time.php'
						// [...] For export, times are given without timezone information. [...]
						$date = new Datetime($queryResult[0], new DateTimeZone('UTC'));
						$dateString = $date->format('c');
					}
					$responseText = wfMsg('ce_com_edited');
					$summary = wfMsg('ce_com_edit_sum'); 
				} else {
					$responseText = wfMsg('ce_com_created');
					$summary = wfMsg('ce_com_create_sum');
				}
				$pageContent = str_replace('##DATE##', $dateString, $pageContent);
				$article->doEdit( $pageContent, $summary );

				if($article->exists()) {
					return CECommentUtils::createXMLResponse(
						$responseText,
						self::SUCCESS, $pageName);
				} else {
					return CECommentUtils::createXMLResponse(
						wfMsg('ce_com_cannot_create'),
						self::PERMISSION_ERROR, $pageName);
				}
			}
		}
	}
}

