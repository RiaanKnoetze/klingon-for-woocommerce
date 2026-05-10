/**
 * Klingon pIqaD transliterator.
 *
 * Walks the DOM and replaces Latin-Okrandian Klingon text in text nodes
 * with KLI pIqaD Private Use Area codepoints (U+F8D0 - U+F8F9), so the
 * bundled pIqaD font can render proper glyphs.
 *
 * Skips form inputs (<input>, <textarea>, contenteditable) so users can
 * still type Latin Klingon without the page rewriting their input. Also
 * skips <script>, <style>, <code>, <pre>, <kbd>, <samp> for the same reason.
 *
 * Uses MutationObserver to handle React/Block-Editor content that mounts
 * after initial DOM ready.
 *
 * Codepoint reference (KLI registry, Unicode PUA):
 *   a=F8D0  b=F8D1  ch=F8D2  D=F8D3  e=F8D4  gh=F8D5  H=F8D6  I=F8D7
 *   j=F8D8  l=F8D9  m=F8DA   n=F8DB  ng=F8DC o=F8DD   p=F8DE  q=F8DF
 *   Q=F8E0  r=F8E1  S=F8E2   t=F8E3  tlh=F8E4 u=F8E5  v=F8E6  w=F8E7
 *   y=F8E8  '=F8E9
 *   0=F8F0..9=F8F9
 */

( function () {
	'use strict';

	function chr( cp ) {
		return String.fromCharCode( cp );
	}

	// Latin Klingon -> KLI pIqaD PUA codepoints.
	// Multi-character letters (tlh, ch, gh, ng) MUST come first in the
	// regex alternation so the longest match wins.
	var MAP = {
		'tlh': chr( 0xF8E4 ),
		'ch':  chr( 0xF8D2 ),
		'gh':  chr( 0xF8D5 ),
		'ng':  chr( 0xF8DC ),
		'a':   chr( 0xF8D0 ),
		'b':   chr( 0xF8D1 ),
		'D':   chr( 0xF8D3 ),
		'e':   chr( 0xF8D4 ),
		'H':   chr( 0xF8D6 ),
		'I':   chr( 0xF8D7 ),
		'j':   chr( 0xF8D8 ),
		'l':   chr( 0xF8D9 ),
		'm':   chr( 0xF8DA ),
		'n':   chr( 0xF8DB ),
		'o':   chr( 0xF8DD ),
		'p':   chr( 0xF8DE ),
		'q':   chr( 0xF8DF ),
		'Q':   chr( 0xF8E0 ),
		'r':   chr( 0xF8E1 ),
		'S':   chr( 0xF8E2 ),
		't':   chr( 0xF8E3 ),
		'u':   chr( 0xF8E5 ),
		'v':   chr( 0xF8E6 ),
		'w':   chr( 0xF8E7 ),
		'y':   chr( 0xF8E8 ),
		"'":   chr( 0xF8E9 ),
		'0':   chr( 0xF8F0 ),
		'1':   chr( 0xF8F1 ),
		'2':   chr( 0xF8F2 ),
		'3':   chr( 0xF8F3 ),
		'4':   chr( 0xF8F4 ),
		'5':   chr( 0xF8F5 ),
		'6':   chr( 0xF8F6 ),
		'7':   chr( 0xF8F7 ),
		'8':   chr( 0xF8F8 ),
		'9':   chr( 0xF8F9 )
	};

	var PATTERN = /tlh|ch|gh|ng|[a-zA-Z'0-9]/g;

	var SKIP_TAGS = {
		SCRIPT:   true,
		STYLE:    true,
		TEXTAREA: true,
		INPUT:    true,
		CODE:     true,
		PRE:      true,
		KBD:      true,
		SAMP:     true
	};

	function shouldSkip( textNode ) {
		var parent = textNode.parentElement;
		if ( ! parent ) {
			return true;
		}
		if ( SKIP_TAGS[ parent.tagName ] ) {
			return true;
		}
		// Walk up looking for contenteditable.
		var el = parent;
		while ( el ) {
			if ( el.isContentEditable ) {
				return true;
			}
			el = el.parentElement;
		}
		return false;
	}

	function transliterate( text ) {
		return text.replace( PATTERN, function ( match ) {
			return MAP[ match ] || match;
		} );
	}

	function processTextNode( node ) {
		// Idempotency: once we've transliterated a node, tag the parent
		// element so re-walks (e.g. via MutationObserver) skip the work.
		if ( ! node.parentElement || node.parentElement.dataset.klingonPiqad === '1' ) {
			return;
		}
		var original = node.nodeValue;
		var transformed = transliterate( original );
		if ( transformed !== original ) {
			node.nodeValue = transformed;
			node.parentElement.dataset.klingonPiqad = '1';
		}
	}

	function walkAndTransform( root ) {
		if ( ! root || root.nodeType !== Node.ELEMENT_NODE ) {
			return;
		}
		var walker = document.createTreeWalker(
			root,
			NodeFilter.SHOW_TEXT,
			{
				acceptNode: function ( node ) {
					return shouldSkip( node )
						? NodeFilter.FILTER_REJECT
						: NodeFilter.FILTER_ACCEPT;
				}
			}
		);
		var nodes = [];
		var n;
		while ( ( n = walker.nextNode() ) ) {
			nodes.push( n );
		}
		for ( var i = 0; i < nodes.length; i++ ) {
			processTextNode( nodes[ i ] );
		}
	}

	function handleAddedNode( node ) {
		if ( node.nodeType === Node.ELEMENT_NODE ) {
			walkAndTransform( node );
		} else if ( node.nodeType === Node.TEXT_NODE && ! shouldSkip( node ) ) {
			processTextNode( node );
		}
	}

	function init() {
		walkAndTransform( document.body );

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
