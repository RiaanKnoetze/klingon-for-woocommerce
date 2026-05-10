/**
 * Klingon pIqaD post-processor.
 *
 * Scans DOM text nodes for marker pairs that the server-side gettext filter
 * placed around actually-translated Klingon strings. For each marked region:
 *
 *   1. Splits the surrounding text node so the marked content sits in its
 *      own text fragment.
 *   2. Wraps that fragment in <span class="klingon-piqad-text">…</span> so
 *      the bundled CSS can apply the pIqaD font scoped to translated text.
 *   3. Strips the marker characters.
 *
 * Untranslated English strings have no markers and get left alone — the
 * pIqaD font never touches them, so admin-bar items WP hasn't translated
 * yet stay readable in English.
 *
 * Markers are two unused codepoints in the KLI Private Use Area
 * (U+F8FA / U+F8FB) — invisible to most fonts, harmless if they leak into
 * attributes or other contexts.
 *
 * MutationObserver picks up React/Block-Editor mounts that happen after
 * initial DOM ready.
 */

( function () {
	'use strict';

	// Same start/end codepoints the PHP filter wraps translations with.
	var MARK_START = String.fromCharCode( 0xF8FA );
	var MARK_END   = String.fromCharCode( 0xF8FB );

	// Match `MARK_START … MARK_END` non-greedily across a single text node.
	var MARK_RE = new RegExp( MARK_START + '([\\s\\S]*?)' + MARK_END, 'g' );

	var SKIP_TAGS = {
		SCRIPT:   true,
		STYLE:    true
	};

	function isInsideSkippedTag( textNode ) {
		var el = textNode.parentElement;
		while ( el ) {
			if ( SKIP_TAGS[ el.tagName ] ) {
				return true;
			}
			el = el.parentElement;
		}
		return false;
	}

	/**
	 * Replace a single text node containing one or more marker pairs with a
	 * sequence of plain text + <span class="klingon-piqad-text"> nodes.
	 */
	function processTextNode( node ) {
		var raw = node.nodeValue;
		if ( raw.indexOf( MARK_START ) === -1 ) {
			return;
		}

		var parent = node.parentNode;
		if ( ! parent ) {
			return;
		}

		var frag = document.createDocumentFragment();
		var lastIndex = 0;
		var m;
		MARK_RE.lastIndex = 0;
		while ( ( m = MARK_RE.exec( raw ) ) !== null ) {
			// Plain text before the marker.
			if ( m.index > lastIndex ) {
				frag.appendChild( document.createTextNode( raw.slice( lastIndex, m.index ) ) );
			}
			// The marked region wrapped in a span.
			var span = document.createElement( 'span' );
			span.className = 'klingon-piqad-text';
			span.textContent = m[ 1 ];
			frag.appendChild( span );
			lastIndex = m.index + m[ 0 ].length;
		}
		// Trailing plain text.
		if ( lastIndex < raw.length ) {
			frag.appendChild( document.createTextNode( raw.slice( lastIndex ) ) );
		}

		parent.replaceChild( frag, node );
	}

	function walkAndWrap( root ) {
		if ( ! root || root.nodeType !== Node.ELEMENT_NODE ) {
			return;
		}
		var walker = document.createTreeWalker(
			root,
			NodeFilter.SHOW_TEXT,
			{
				acceptNode: function ( node ) {
					if ( isInsideSkippedTag( node ) ) {
						return NodeFilter.FILTER_REJECT;
					}
					return node.nodeValue.indexOf( MARK_START ) !== -1
						? NodeFilter.FILTER_ACCEPT
						: NodeFilter.FILTER_SKIP;
				}
			}
		);
		var nodes = [];
		var n;
		while ( ( n = walker.nextNode() ) ) {
			nodes.push( n );
		}
		// Process collected nodes outside the walker — replaceChild during
		// iteration would disturb the walk.
		for ( var i = 0; i < nodes.length; i++ ) {
			processTextNode( nodes[ i ] );
		}
	}

	function handleAddedNode( node ) {
		if ( node.nodeType === Node.ELEMENT_NODE ) {
			walkAndWrap( node );
		} else if ( node.nodeType === Node.TEXT_NODE && ! isInsideSkippedTag( node ) ) {
			if ( node.nodeValue.indexOf( MARK_START ) !== -1 ) {
				processTextNode( node );
			}
		}
	}

	function init() {
		walkAndWrap( document.body );

		var observer = new MutationObserver( function ( mutations ) {
			for ( var i = 0; i < mutations.length; i++ ) {
				var added = mutations[ i ].addedNodes;
				for ( var j = 0; j < added.length; j++ ) {
					handleAddedNode( added[ j ] );
				}
			}
		} );

		observer.observe( document.body, {
			childList: true,
			subtree:   true
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
