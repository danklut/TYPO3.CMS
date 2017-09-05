'use strict';(function(d){"object"==typeof exports&&"object"==typeof module?d(require("../../lib/codemirror")):"function"==typeof define&&define.amd?define(["../../lib/codemirror"],d):d(CodeMirror)})(function(d){d.defineMode("oz",function(d){function e(a){return new RegExp("^(("+a.join(")|(")+"))\\b")}function f(a,b){if(a.eatSpace())return null;if(a.match(/[{}]/))return"bracket";if(a.match(/(\[])/))return"keyword";if(a.match(n)||a.match(p))return"operator";if(a.match(q))return"atom";var c=a.match(r);
if(c)return b.doInCurrentLine?b.doInCurrentLine=!1:b.currentIndent++,"proc"==c[0]||"fun"==c[0]?b.tokenize=t:"class"==c[0]?b.tokenize=u:"meth"==c[0]&&(b.tokenize=v),"keyword";if(a.match(g)||a.match(w))return"keyword";if(a.match(h))return b.currentIndent--,"keyword";c=a.next();if('"'==c||"'"==c)return b.tokenize=x(c),b.tokenize(a,b);if(/[~\d]/.test(c)){if("~"==c){if(!/^[0-9]/.test(a.peek()))return null;if("0"==a.next()&&a.match(/^[xX][0-9a-fA-F]+/)||a.match(/^[0-9]*(\.[0-9]+)?([eE][~+]?[0-9]+)?/))return"number"}return"0"==
c&&a.match(/^[xX][0-9a-fA-F]+/)||a.match(/^[0-9]*(\.[0-9]+)?([eE][~+]?[0-9]+)?/)?"number":null}if("%"==c)return a.skipToEnd(),"comment";if("/"==c&&a.eat("*"))return b.tokenize=k,k(a,b);if(y.test(c))return"operator";a.eatWhile(/\w/);return"variable"}function u(a,b){if(a.eatSpace())return null;a.match(/([A-Z][A-Za-z0-9_]*)|(`.+`)/);b.tokenize=f;return"variable-3"}function v(a,b){if(a.eatSpace())return null;a.match(/([a-zA-Z][A-Za-z0-9_]*)|(`.+`)/);b.tokenize=f;return"def"}function t(a,b){if(a.eatSpace())return null;
if(!b.hasPassedFirstStage&&a.eat("{"))return b.hasPassedFirstStage=!0,"bracket";if(b.hasPassedFirstStage)return a.match(/([A-Z][A-Za-z0-9_]*)|(`.+`)|\$/),b.hasPassedFirstStage=!1,b.tokenize=f,"def";b.tokenize=f;return null}function k(a,b){for(var c=!1,d;d=a.next();){if("/"==d&&c){b.tokenize=f;break}c="*"==d}return"comment"}function x(a){return function(b,c){for(var d=!1,e,g=!1;null!=(e=b.next());){if(e==a&&!d){g=!0;break}d=!d&&"\\"==e}if(g||!d)c.tokenize=f;return"string"}}var y=/[\^@!\|<>#~\.\*\-\+\\/,=]/,
p=/(<-)|(:=)|(=<)|(>=)|(<=)|(<:)|(>:)|(=:)|(\\=)|(\\=:)|(!!)|(==)|(::)/,n=/(:::)|(\.\.\.)|(=<:)|(>=:)/,l="in then else of elseof elsecase elseif catch finally with require prepare import export define do".split(" "),m=["end"],q=e(["true","false","nil","unit"]),w=e("andthen at attr declare feat from lex mod div mode orelse parser prod prop scanner self syn token".split(" ")),r=e("local proc fun case class if cond or dis choice not thread try raise lock for suchthat meth functor".split(" ")),g=e(l),
h=e(m);return{startState:function(){return{tokenize:f,currentIndent:0,doInCurrentLine:!1,hasPassedFirstStage:!1}},token:function(a,b){a.sol()&&(b.doInCurrentLine=0);return b.tokenize(a,b)},indent:function(a,b){b=b.replace(/^\s+|\s+$/g,"");return b.match(h)||b.match(g)||b.match(/(\[])/)?d.indentUnit*(a.currentIndent-1):0>a.currentIndent?0:a.currentIndent*d.indentUnit},fold:"indent",electricInput:function(){var a=l.concat(m);return new RegExp("[\\[\\]]|("+a.join("|")+")$")}(),lineComment:"%",blockCommentStart:"/*",
blockCommentEnd:"*/"}});d.defineMIME("text/x-oz","oz")});