(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["eggs"],{"12bb":function(e,t,n){"use strict";n.r(t);var r=function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("my-overlay",[n("div",{attrs:{id:"eggs"}}),n("my-prompt",{attrs:{show:e.promptShow},on:{confirm:e.onConfirm}},[n("div",{staticClass:"prize_eggs"},[3===e.prizeType?n("img",{staticClass:"card_prize",attrs:{src:e.cardImg,alt:"奖品"}}):n("img",{staticClass:"icon_prize",attrs:{src:e.prizes[e.currentMark],alt:"奖品"}}),n("span",[e._v("恭喜获得"),n("span",{directives:[{name:"show",rawName:"v-show",value:e.prizeAmount,expression:"prizeAmount"}]},[e._v(e._s(e.prizeAmount)+"个")]),e._v(e._s(e.prizeName))])])])],1)},a=[],i=(n("a9e3"),n("d3b7"),n("96cf"),n("a4d3"),n("4de4"),n("4160"),n("b680"),n("e439"),n("dbb4"),n("b64b"),n("159b"),n("2909"));function s(e){if(Array.isArray(e))return e}n("e01a"),n("d28b"),n("e260"),n("0d03"),n("25f0"),n("3ca3"),n("ddb0");function o(e,t){if(Symbol.iterator in Object(e)||"[object Arguments]"===Object.prototype.toString.call(e)){var n=[],r=!0,a=!1,i=void 0;try{for(var s,o=e[Symbol.iterator]();!(r=(s=o.next()).done);r=!0)if(n.push(s.value),t&&n.length===t)break}catch(c){a=!0,i=c}finally{try{r||null==o["return"]||o["return"]()}finally{if(a)throw i}}return n}}function c(){throw new TypeError("Invalid attempt to destructure non-iterable instance")}function u(e,t){return s(e)||o(e,t)||c()}var p=n("d4ec"),g=n("bee2"),l=n("ade3"),h=n("9f8e"),f=n("6564"),d=n("4360"),m=n("ff7c");function b(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function _(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?b(Object(n),!0).forEach((function(t){Object(l["a"])(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):b(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}var v=window.innerHeight/window.innerWidth>=1.85,w=function(){function e(){Object(p["a"])(this,e),Object(l["a"])(this,"_scene",null),Object(l["a"])(this,"_layer",null),Object(l["a"])(this,"_canvasWidth",750),Object(l["a"])(this,"_canvasHeight",v?1624:1334),Object(l["a"])(this,"_groupBg",null),Object(l["a"])(this,"_eggsNums",null),Object(l["a"])(this,"_breaking",!1),Object(l["a"])(this,"_hooks",{onClose:null,onBreaked:null}),this._init()}return Object(g["a"])(e,[{key:"listen",value:function(e){this._hooks=_({},e)}},{key:"resetEggsView",value:function(){this._layer.clear(),this._breaking=!1,this._createStage()}},{key:"_init",value:function(){var e,t;return regeneratorRuntime.async((function(n){while(1)switch(n.prev=n.next){case 0:return e=this._canvasWidth,t=this._canvasHeight,this._scene=new h["c"]("#eggs",{viewport:["auto","auto"],resolution:[e,t]}),this._layer=this._scene.layer(),n.next=5,regeneratorRuntime.awrap(this._loading());case 5:this._createStage();case 6:case"end":return n.stop()}}),null,this)}},{key:"_createStage",value:function(){var e=this,t=[585,880],n=this._canvasWidth,r=this._canvasHeight,a=new h["a"];a.attr({bgimage:{src:"img_pop-up.png",display:"stretch"},size:t,anchor:.5,pos:[n/2,r/2]});var i=new h["d"];i.attr({textures:"btn_close.png",size:[70,70],anchor:[.5,.5],pos:[n/2+t[0]/2-12,r/2-t[1]/2+55]});var s=new h["d"];s.attr({textures:"img_name_cd.png",size:[101,55],pos:[246,25]});var o=new h["b"];o.attr({text:"次数: ".concat(d["a"].state.userInfo.eggsNums),font:"small-caps bold 35px Arial",color:"white",width:585,textAlign:"center",pos:[0,754]}),a.append(s,o),this._layer.append(a,i),this._eggsNums=o,this._createEggs(a),this._touchAnimated(i,(function(){e._hooks.onClose&&e._hooks.onClose()})),this._groupBg=a}},{key:"_createEggs",value:function(e){var t=[475,575],n=[55,169],r=new h["a"];r.attr({size:t,pos:n,display:"flex",alignItems:"center",justifyContent:"space-between",flexWrap:"wrap"});for(var a=0;a<9;a++){var i=new h["a"];i.attr({size:[156,156]});var s=new h["d"];s.attr({textures:"img_egg_default.png",size:[156,156]}),i.append(s),r.append(i),this._breakEggEvent(i)}e.append(r)}},{key:"_breakEggView",value:function(e,t){var n=new h["d"];n.attr({textures:"img_light.png",size:[132,132],pos:[81,66],anchor:.5});var r=new h["d"];r.attr({textures:t,size:[60,60],pos:[48,0]});var a=new h["d"];a.attr({textures:"img_egg_active.png",size:[156,156]}),n.animate([{rotate:0},{rotate:360}],{duration:1800,iterations:1/0}),e.append(n,r,a)}},{key:"_breakingAnimate",value:function(e){var t,n,r,a;return regeneratorRuntime.async((function(i){while(1)switch(i.prev=i.next){case 0:return t=u(e,2),n=t[0],r=t[1],n=n+55+156,r=r+169+90,a=new h["d"],a.attr({textures:"icon_hammer.png",size:[90,90],anchor:[1,1],pos:[n,r]}),this._groupBg.append(a),i.next=8,regeneratorRuntime.awrap(a.transition(.2).attr({rotate:45}));case 8:return i.next=10,regeneratorRuntime.awrap(a.transition(.05).attr({rotate:0}));case 10:return i.abrupt("return",!0);case 11:case"end":return i.stop()}}),null,this)}},{key:"_breakEggEvent",value:function(e){var t=this;e.on("click",(function(n){var r;return regeneratorRuntime.async((function(a){while(1)switch(a.prev=a.next){case 0:if(n.stopDispatch(),!t._breaking){a.next=3;break}return a.abrupt("return",!1);case 3:return t._breaking=!0,a.next=6,regeneratorRuntime.awrap(Object(m["a"])("breakEggs"));case 6:if(r=a.sent,r){a.next=9;break}return a.abrupt("return",!1);case 9:return a.next=11,regeneratorRuntime.awrap(t._breakingAnimate(e.xy));case 11:e.clear(),t._breakEggView(e,r.img),t._hooks.onBreaked&&t._hooks.onBreaked(r);case 14:case"end":return a.stop()}}))}))}},{key:"_touchAnimated",value:function(e,t){e.on("touchstart",(function(t){t.stopDispatch(),e.attr({scale:.95})})),e.on("touchend",(function(n){n.stopDispatch(),e.attr({scale:1}),t&&t()}))}},{key:"_loading",value:function(){var e;return regeneratorRuntime.async((function(t){while(1)switch(t.prev=t.next){case 0:return this._scene.on("preload",(function(e){console.log("加载中... ".concat(100*(e.loaded.length/e.resources.length).toFixed(2),"%"))})),t.next=3,regeneratorRuntime.awrap((e=this._scene).preload.apply(e,Object(i["a"])(f["a"].eggs)));case 3:case"end":return t.stop()}}),null,this)}}]),e}(),y=w,k={name:"eggs",data:function(){return{promptShow:!1,prizes:{0:n("9042"),1:n("5fa4"),2:n("413d"),3:n("44eb")},prizeName:"",prizeAmount:0,prizeType:-1,currentMark:"",cardImg:"",eggs:null}},mounted:function(){this.gameInit()},methods:{gameInit:function(){var e=this,t=new y;this.eggs=t,t.listen({onClose:function(){e.$router.back(-1)},onBreaked:function(t){var n=t.prizeName,r=t.prizeMark,a=t.cardImg,i=t.amount,s=t.type;e.prizeName=n,e.prizeType=s,e.currentMark=Number(r),e.cardImg=a,e.prizeAmount=i;var o=e.$store.state.userInfo,c=o.eggsNums-1;c>=0&&e.$store.commit("updateUserInfo",{eggsNums:c}),setTimeout((function(){e.$nativeApi._watchAD((function(){return regeneratorRuntime.async((function(t){while(1)switch(t.prev=t.next){case 0:e.promptShow=!0,0===s&&e.$store.commit("updateUserInfo",{gold:o.gold+i});case 2:case"end":return t.stop()}}))}))}),300)}})},onConfirm:function(){this.promptShow=!1,this.eggs.resetEggsView()}}},x=k,O=(n("e971"),n("2877")),j=Object(O["a"])(x,r,a,!1,null,"91efd0e8",null);t["default"]=j.exports},"44eb":function(e,t,n){e.exports=n.p+"img/icon_draft.84ae9986.png"},"5fa4":function(e,t,n){e.exports=n.p+"img/icon_cdb.cf9383c5.png"},9042:function(e,t,n){e.exports=n.p+"img/icon_gold.6aa0fc61.png"},9516:function(e,t,n){},e971:function(e,t,n){"use strict";var r=n("9516"),a=n.n(r);a.a}}]);