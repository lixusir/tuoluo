(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["turntable"],{"2ec2":function(t,e,n){"use strict";var r=n("306b"),i=n.n(r);i.a},"306b":function(t,e,n){},"6e28":function(t,e,n){t.exports=n.p+"img/prize_egg.0c82166f.png"},"828f":function(t,e,n){"use strict";n.r(e);var r=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("my-overlay",[n("div",{attrs:{id:"turntable"}}),n("my-prompt",{attrs:{show:t.promptShow},on:{confirm:t.onConfirm}},[n("div",{staticClass:"prize_eggs"},[n("img",{staticClass:"card_prize",attrs:{src:t.prizeImg[t.resultIndex],alt:"奖品"}}),n("span",[t._v("恭喜获得"+t._s(t.prizeAmount)+t._s(t.prizeName[t.resultIndex]))])])])],1)},i=[],a=(n("a4d3"),n("4de4"),n("4160"),n("a9e3"),n("e439"),n("dbb4"),n("b64b"),n("d3b7"),n("159b"),n("96cf"),n("ade3")),s=(n("45fc"),n("b0c0"),n("b680"),n("2909")),o=n("d4ec"),c=n("bee2"),u=n("9f8e"),p=n("6564"),h=n("ff7c"),l=n("4360");function b(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),n.push.apply(n,r)}return n}function f(t){for(var e=1;e<arguments.length;e++){var n=null!=arguments[e]?arguments[e]:{};e%2?b(Object(n),!0).forEach((function(e){Object(a["a"])(t,e,n[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):b(Object(n)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(n,e))}))}return t}var d=window.innerHeight/window.innerWidth>=1.85,_=function(){function t(e){Object(o["a"])(this,t),Object(a["a"])(this,"_scene",null),Object(a["a"])(this,"_layer",null),Object(a["a"])(this,"_canvasWidth",750),Object(a["a"])(this,"_canvasHeight",d?1524:1334),Object(a["a"])(this,"_table",null),Object(a["a"])(this,"_prizeList",[]),Object(a["a"])(this,"_data",null),Object(a["a"])(this,"_currentWinIndex",-1),Object(a["a"])(this,"_turning",!1),Object(a["a"])(this,"_ticketLabel",null),Object(a["a"])(this,"_theResult",null),Object(a["a"])(this,"_startBtn",null),Object(a["a"])(this,"_isOver",!1),Object(a["a"])(this,"_hooks",{onClose:null,onResult:null}),this._init(),this._data=e}return Object(c["a"])(t,[{key:"listen",value:function(t){this._hooks=f({},t)}},{key:"_init",value:function(){var t,e;return regeneratorRuntime.async((function(n){while(1)switch(n.prev=n.next){case 0:return t=this._canvasWidth,e=this._canvasHeight,this._scene=new u["c"]("#turntable",{viewport:["auto","auto"],resolution:[t,e]}),this._layer=this._scene.layer(),n.next=5,regeneratorRuntime.awrap(this._loading());case 5:this._createStage(),this._placePrize();case 7:case"end":return n.stop()}}),null,this)}},{key:"_createStage",value:function(){var t=this,e=new u["d"];e.attr({textures:"bg_img_title.png",size:[445,238],pos:[153,100]});var n=new u["a"];n.attr({bgimage:{display:"stretch",src:"turntable_bg.png"},size:[470,468],pos:[140,714]});var r=new u["d"];r.attr({textures:"btn_close.png",size:[70,70],anchor:[.5,.5],pos:[703,126]});var i=new u["d"];i.attr({textures:"prize_ticket.png",size:[70,62],pos:[97,166]});var a=new u["b"];a.attr({text:"转盘券: ".concat(l["a"].state.userInfo.ticket),font:"24px bold AkrobatBloack",color:"white",pos:[167,185],width:230,textAlign:"right"});var s=new u["d"];s.attr({textures:"btn_start.png",anchor:.5,size:[320,157],pos:[231,320.5]});var o=new u["a"];o.attr({bgimage:{src:"turntable.png",display:"stretch"},anchor:.5,size:[680,680],pos:[381,556]}),n.append(s,i,a),this._layer.append(e,n,o,r),this._ticketLabel=a,this._startBtn=s,this._touchAnimated(s,(function(){t._startTurntable()}),!0),this._touchAnimated(r,this._hooks.onClose),0===Number(l["a"].state.userInfo.ticket)&&0===Number(l["a"].state.userInfo.invitedTicket)&&(this._isOver=!0,this._startBtn.attr({filter:{saturate:"5%"}}))}},{key:"_placePrize",value:function(){var t=this,e=680,n=150,r=e/2-n/2,i=[e/2,e/2],a=new u["a"];a.attr({anchor:.5,size:[e,e],pos:[41+e/2,216+e/2],rotate:180}),this._data.forEach((function(e,n){var s=i[0]+Math.sin(2*Math.PI/360*(40*n))*r,o=i[1]+Math.cos(2*Math.PI/360*(40*n))*r,c=new u["a"];c.attr({bgimage:{src:"content_icon_dial.png",display:"stretch"},anchor:.5,size:[150,150],pos:[s,o],rotate:180-40*n});var p=new u["d"];p.attr({textures:e.img,anchor:.5,pos:[75,50],height:60});var h=new u["b"];h.attr({text:e.name,font:"20px bold AkrobatBloack",color:"#E1C556",width:150,textAlign:"center",pos:[0,80]}),c.append(p,h),a.append(c),t._prizeList.push(c)})),this._layer.append(a),this._table=a}},{key:"_transition",value:function(t,e,n){return regeneratorRuntime.async((function(r){while(1)switch(r.prev=r.next){case 0:return r.prev=0,r.next=3,regeneratorRuntime.awrap(t.transition(.2).attr({rotate:40*n+180}));case 3:e--,n++,e>0?this._transition(t,e,n):(this._prizeList[this._currentWinIndex].attr({bgimage:{src:"content_icon_dial_selected.png",display:"stretch"}}),this._turning=!1,0===Number(l["a"].state.userInfo.ticket)&&l["a"].commit("updateUserInfo",{invitedTicket:Number(l["a"].state.userInfo.invitedTicket)-1}),this._hooks.onResult&&this._hooks.onResult(this._theResult),this._ticketLabel.attr({text:"转盘券: ".concat(l["a"].state.userInfo.ticket)}),0===Number(l["a"].state.userInfo.ticket)&&0===Number(l["a"].state.userInfo.invitedTicket)&&(this._isOver=!0,this._startBtn.attr({filter:{saturate:"5%"}}))),r.next=11;break;case 8:r.prev=8,r.t0=r["catch"](0),console.warn(r.t0);case 11:case"end":return r.stop()}}),null,this,[[0,8]])}},{key:"_startTurntable",value:function(){var t,e;return regeneratorRuntime.async((function(n){while(1)switch(n.prev=n.next){case 0:return-1!==this._currentWinIndex&&this._prizeList[this._currentWinIndex].attr({bgimage:{src:"content_icon_dial.png",display:"stretch"}}),n.next=3,regeneratorRuntime.awrap(Object(h["a"])("toTurn"));case 3:if(t=n.sent,this._theResult=t,t){n.next=7;break}return n.abrupt("return",!1);case 7:if(e=-1,this._data.some((function(n,r){if(n.id===t.id)return e=r,!0})),-1!==e){n.next=11;break}return n.abrupt("return",alert("返回数据出错"));case 11:this._table.attr({rotate:0}),this._currentWinIndex=e,this._turning=!0,this._transition(this._table,10+e,0);case 15:case"end":return n.stop()}}),null,this)}},{key:"_touchAnimated",value:function(t,e,n){var r=this;t.on("touchstart",(function(e){return(!n||!r._isOver)&&(!r._turning&&(e.stopDispatch(),void t.attr({scale:.95})))})),t.on("touchend",(function(i){return(!n||!r._isOver)&&(!r._turning&&(i.stopDispatch(),t.attr({scale:1}),void(e&&e())))}))}},{key:"_loading",value:function(){var t;return regeneratorRuntime.async((function(e){while(1)switch(e.prev=e.next){case 0:return this._scene.on("preload",(function(t){console.log("加载中... ".concat(100*(t.loaded.length/t.resources.length).toFixed(2),"%"))})),e.next=3,regeneratorRuntime.awrap((t=this._scene).preload.apply(t,Object(s["a"])(p["a"].turntable)));case 3:case"end":return e.stop()}}),null,this)}}]),t}(),g=_,m=n("2f62");function v(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),n.push.apply(n,r)}return n}function w(t){for(var e=1;e<arguments.length;e++){var n=null!=arguments[e]?arguments[e]:{};e%2?v(Object(n),!0).forEach((function(e){Object(a["a"])(t,e,n[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):v(Object(n)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(n,e))}))}return t}var O={name:"turntable",data:function(){return{prizeName:["个金币","个彩蛋币","块钱","个转盘券"],prizeImg:[n("a71c"),n("6e28"),n("d861"),n("b3de")],promptShow:!1,prizeAmount:0,resultIndex:0}},computed:w({},Object(m["b"])(["userInfo"]),{goldIncreasing:function(t){var e=0;return t.birdList.forEach((function(t){e+=t.income||0})),e}}),mounted:function(){this.getData()},methods:{getData:function(){var t,e,n=this;return regeneratorRuntime.async((function(r){while(1)switch(r.prev=r.next){case 0:return r.next=2,regeneratorRuntime.awrap(this.$ajax("turntableList"));case 2:if(t=r.sent,t){r.next=5;break}return r.abrupt("return",!1);case 5:e=new g(t),e.listen({onClose:function(){n.$router.back(-1)},onResult:function(t){var e=t.type,r=t.amount;n.resultIndex=Number(e),n.prizeAmount=r,n.promptShow=!0;var i=n.userInfo.ticket-1;"3"===e?i+=r:"0"===e&&n.$store.commit("updateUserInfo",{gold:Number(n.userInfo.gold)+r}),n.$store.commit("updateUserInfo",{ticket:i>=0?i:0})}});case 7:case"end":return r.stop()}}),null,this)},onConfirm:function(){var t=this;if(1===this.resultIndex)return this.$nativeApi._watchAD((function(){t.promptShow=!1})),!1;this.promptShow=!1}}},y=O,j=(n("2ec2"),n("2877")),k=Object(j["a"])(y,r,i,!1,null,"1c4ec84c",null);e["default"]=k.exports},a71c:function(t,e,n){t.exports=n.p+"img/prize_gold.f2d4e6b3.png"},b3de:function(t,e,n){t.exports=n.p+"img/prize_ticket.7a6fa1f1.png"},d861:function(t,e,n){t.exports=n.p+"img/prize_red_packet.81f9df12.png"}}]);