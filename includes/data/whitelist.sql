-- WP Span Checker bundled whitelist seed.
-- These statements are parsed by the plugin and imported with INSERT IGNORE semantics.
-- Comprehensive list of trusted email providers.

-- =====================================================
-- MAJOR GLOBAL PROVIDERS
-- =====================================================

-- Google
INSERT INTO span_whitelist_domains (domain) VALUES ('gmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('googlemail.com');

-- Microsoft / Outlook / Hotmail / Live
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('msn.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.co.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('hotmail.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('outlook.in');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.be');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.in');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.se');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.dk');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('live.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('passport.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('windowslive.com');

-- Yahoo
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.co.in');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.co.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.mx');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.ar');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.sg');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.hk');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.tw');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.co.nz');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.co.id');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.co.th');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.ie');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.gr');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.se');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.dk');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.be');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.ro');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.hu');
INSERT INTO span_whitelist_domains (domain) VALUES ('ymail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('rocketmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.ph');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.vn');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoo.com.cn');
INSERT INTO span_whitelist_domains (domain) VALUES ('myyahoo.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('yahoomail.com');

-- Apple
INSERT INTO span_whitelist_domains (domain) VALUES ('icloud.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('me.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('mac.com');

-- AOL
INSERT INTO span_whitelist_domains (domain) VALUES ('aol.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('aol.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('aol.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('aol.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('aol.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('aim.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('netscape.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('compuserve.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('cs.com');

-- =====================================================
-- PRIVACY-FOCUSED PROVIDERS
-- =====================================================

-- ProtonMail
INSERT INTO span_whitelist_domains (domain) VALUES ('proton.me');
INSERT INTO span_whitelist_domains (domain) VALUES ('protonmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('protonmail.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('pm.me');

-- Tutanota
INSERT INTO span_whitelist_domains (domain) VALUES ('tutanota.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('tutanota.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('tuta.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('tuta.io');
INSERT INTO span_whitelist_domains (domain) VALUES ('keemail.me');

-- Other Privacy Providers
INSERT INTO span_whitelist_domains (domain) VALUES ('posteo.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('posteo.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('mailbox.org');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.fm');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.us');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.cn');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.im');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.in');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastmail.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('runbox.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('runbox.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('hushmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('hushmail.me');
INSERT INTO span_whitelist_domains (domain) VALUES ('hush.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('hush.ai');
INSERT INTO span_whitelist_domains (domain) VALUES ('mailfence.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('disroot.org');
INSERT INTO span_whitelist_domains (domain) VALUES ('kolabnow.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('startmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('countermail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('ctemplar.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('aikq.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('soverin.net');

-- =====================================================
-- BUSINESS / PROFESSIONAL PROVIDERS
-- =====================================================

-- Zoho
INSERT INTO span_whitelist_domains (domain) VALUES ('zoho.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('zohomail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('zohomail.eu');
INSERT INTO span_whitelist_domains (domain) VALUES ('zohomail.in');

-- Basecamp Hey
INSERT INTO span_whitelist_domains (domain) VALUES ('hey.com');

-- Titan
INSERT INTO span_whitelist_domains (domain) VALUES ('titan.email');

-- =====================================================
-- GERMAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('gmx.us');
INSERT INTO span_whitelist_domains (domain) VALUES ('web.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('freenet.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('t-online.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('arcor.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('vodafone.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('1und1.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('ionos.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('online.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('kabel.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('kabelbw.de');
INSERT INTO span_whitelist_domains (domain) VALUES ('unitybox.de');

-- =====================================================
-- FRENCH PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('orange.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('free.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('laposte.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('wanadoo.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('sfr.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('neuf.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('numericable.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('bbox.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('bouygtel.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('bouyguestelecom.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('aliceadsl.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('voila.fr');
INSERT INTO span_whitelist_domains (domain) VALUES ('club-internet.fr');

-- =====================================================
-- ITALIAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('alice.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('virgilio.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('libero.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('tiscali.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('fastwebnet.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('tin.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('pec.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('aruba.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('inwind.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('email.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('poste.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('postecert.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('legalmail.it');
INSERT INTO span_whitelist_domains (domain) VALUES ('tim.it');

-- =====================================================
-- SPANISH / PORTUGUESE PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('terra.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('terra.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('terra.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('telefonica.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('movistar.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('orange.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('ono.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('jazztel.es');
INSERT INTO span_whitelist_domains (domain) VALUES ('bol.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('uol.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('ig.com.br');
INSERT INTO span_whitelist_domains (domain) VALUES ('globo.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('globomail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('r7.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('sapo.pt');
INSERT INTO span_whitelist_domains (domain) VALUES ('clix.pt');
INSERT INTO span_whitelist_domains (domain) VALUES ('netcabo.pt');
INSERT INTO span_whitelist_domains (domain) VALUES ('mail.telepac.pt');

-- =====================================================
-- UK / IRELAND PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('btinternet.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('btopenworld.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('btconnect.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('virginmedia.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('virgin.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('sky.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('ntlworld.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('talktalk.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('talktalk.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('tiscali.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('blueyonder.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('plus.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('plusnet.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('o2.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('vodafone.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('ee.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('three.co.uk');
INSERT INTO span_whitelist_domains (domain) VALUES ('eircom.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('eir.ie');

-- =====================================================
-- SCANDINAVIAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('telia.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('telenor.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('spray.se');
INSERT INTO span_whitelist_domains (domain) VALUES ('swipnet.se');
INSERT INTO span_whitelist_domains (domain) VALUES ('bredband.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('comhem.se');
INSERT INTO span_whitelist_domains (domain) VALUES ('home.se');
INSERT INTO span_whitelist_domains (domain) VALUES ('online.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('start.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('frisurf.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('broadpark.no');
INSERT INTO span_whitelist_domains (domain) VALUES ('jubii.dk');
INSERT INTO span_whitelist_domains (domain) VALUES ('sol.dk');
INSERT INTO span_whitelist_domains (domain) VALUES ('tdcadsl.dk');
INSERT INTO span_whitelist_domains (domain) VALUES ('tele2.dk');
INSERT INTO span_whitelist_domains (domain) VALUES ('surfnet.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('kolumbus.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('inet.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('pp.inet.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('elisa.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('saunalahti.fi');
INSERT INTO span_whitelist_domains (domain) VALUES ('welho.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('simnet.is');

-- =====================================================
-- BENELUX PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('ziggo.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('kpnmail.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('kpnplanet.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('xs4all.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('planet.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('hetnet.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('casema.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('quicknet.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('home.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('wxs.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('chello.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('upcmail.nl');
INSERT INTO span_whitelist_domains (domain) VALUES ('telenet.be');
INSERT INTO span_whitelist_domains (domain) VALUES ('skynet.be');
INSERT INTO span_whitelist_domains (domain) VALUES ('proximus.be');
INSERT INTO span_whitelist_domains (domain) VALUES ('belgacom.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('pt.lu');

-- =====================================================
-- EASTERN EUROPEAN PROVIDERS
-- =====================================================

-- Czech Republic
INSERT INTO span_whitelist_domains (domain) VALUES ('seznam.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('email.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('centrum.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('volny.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('atlas.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('post.cz');
INSERT INTO span_whitelist_domains (domain) VALUES ('tiscali.cz');

-- Poland
INSERT INTO span_whitelist_domains (domain) VALUES ('wp.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('o2.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('interia.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('onet.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('gazeta.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('tlen.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('poczta.onet.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('poczta.fm');
INSERT INTO span_whitelist_domains (domain) VALUES ('op.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('go2.pl');
INSERT INTO span_whitelist_domains (domain) VALUES ('vp.pl');

-- Bulgaria
INSERT INTO span_whitelist_domains (domain) VALUES ('mail.bg');
INSERT INTO span_whitelist_domains (domain) VALUES ('abv.bg');
INSERT INTO span_whitelist_domains (domain) VALUES ('gbg.bg');
INSERT INTO span_whitelist_domains (domain) VALUES ('dir.bg');

-- Romania
INSERT INTO span_whitelist_domains (domain) VALUES ('rdslink.ro');
INSERT INTO span_whitelist_domains (domain) VALUES ('clicknet.ro');

-- Hungary
INSERT INTO span_whitelist_domains (domain) VALUES ('freemail.hu');
INSERT INTO span_whitelist_domains (domain) VALUES ('citromail.hu');
INSERT INTO span_whitelist_domains (domain) VALUES ('indamail.hu');
INSERT INTO span_whitelist_domains (domain) VALUES ('t-online.hu');

-- Slovakia
INSERT INTO span_whitelist_domains (domain) VALUES ('azet.sk');
INSERT INTO span_whitelist_domains (domain) VALUES ('post.sk');
INSERT INTO span_whitelist_domains (domain) VALUES ('zoznam.sk');
INSERT INTO span_whitelist_domains (domain) VALUES ('centrum.sk');

-- Ukraine
INSERT INTO span_whitelist_domains (domain) VALUES ('ukr.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('i.ua');
INSERT INTO span_whitelist_domains (domain) VALUES ('meta.ua');
INSERT INTO span_whitelist_domains (domain) VALUES ('bigmir.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('email.ua');

-- =====================================================
-- RUSSIAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('yandex.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('yandex.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('yandex.ua');
INSERT INTO span_whitelist_domains (domain) VALUES ('yandex.by');
INSERT INTO span_whitelist_domains (domain) VALUES ('yandex.kz');
INSERT INTO span_whitelist_domains (domain) VALUES ('ya.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('mail.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('inbox.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('bk.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('list.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('internet.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('rambler.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('lenta.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('autorambler.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('myrambler.ru');
INSERT INTO span_whitelist_domains (domain) VALUES ('ro.ru');

-- =====================================================
-- CHINESE PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('qq.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('163.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('126.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('vip.163.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('vip.126.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('yeah.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('sina.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('sina.cn');
INSERT INTO span_whitelist_domains (domain) VALUES ('vip.sina.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('sohu.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('tom.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('21cn.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('foxmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('aliyun.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('139.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('189.cn');
INSERT INTO span_whitelist_domains (domain) VALUES ('wo.cn');
INSERT INTO span_whitelist_domains (domain) VALUES ('188.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('263.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('eyou.com');

-- =====================================================
-- JAPANESE PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('ezweb.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('docomo.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('softbank.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('au.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('i.softbank.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('nifty.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('nifty.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('biglobe.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('so-net.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('ocn.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('excite.co.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('infoseek.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('goo.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('plala.or.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('dion.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('ybb.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('jcom.home.ne.jp');
INSERT INTO span_whitelist_domains (domain) VALUES ('me.com');

-- =====================================================
-- KOREAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('naver.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('daum.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('hanmail.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('nate.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('chol.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('korea.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('paran.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('empal.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('dreamwiz.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('kakao.com');

-- =====================================================
-- INDIAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('rediffmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('rediff.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('sify.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('indiatimes.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('in.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('vsnl.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('vsnl.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('dataone.in');
INSERT INTO span_whitelist_domains (domain) VALUES ('bsnl.in');

-- =====================================================
-- SOUTHEAST ASIAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('singnet.com.sg');
INSERT INTO span_whitelist_domains (domain) VALUES ('pacific.net.sg');
INSERT INTO span_whitelist_domains (domain) VALUES ('starhub.net.sg');
INSERT INTO span_whitelist_domains (domain) VALUES ('myjaring.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('tm.net.my');
INSERT INTO span_whitelist_domains (domain) VALUES ('streamyx.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('pd.jaring.my');
INSERT INTO span_whitelist_domains (domain) VALUES ('fpt.vn');
INSERT INTO span_whitelist_domains (domain) VALUES ('vnn.vn');

-- =====================================================
-- AUSTRALIAN / NEW ZEALAND PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('bigpond.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('bigpond.net.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('optusnet.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('ozemail.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('iinet.net.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('internode.on.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('tpg.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('dodo.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('adam.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('primus.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('westnet.com.au');
INSERT INTO span_whitelist_domains (domain) VALUES ('xtra.co.nz');
INSERT INTO span_whitelist_domains (domain) VALUES ('clear.net.nz');
INSERT INTO span_whitelist_domains (domain) VALUES ('ihug.co.nz');
INSERT INTO span_whitelist_domains (domain) VALUES ('paradise.net.nz');
INSERT INTO span_whitelist_domains (domain) VALUES ('vodafone.co.nz');
INSERT INTO span_whitelist_domains (domain) VALUES ('slingshot.co.nz');

-- =====================================================
-- NORTH AMERICAN ISP PROVIDERS
-- =====================================================

-- US ISPs
INSERT INTO span_whitelist_domains (domain) VALUES ('comcast.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('xfinity.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('att.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('verizon.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('cox.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('charter.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('spectrum.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('sbcglobal.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('bellsouth.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('earthlink.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('mindspring.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('optonline.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('optimum.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('frontier.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('frontiernet.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('centurylink.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('centurytel.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('embarqmail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('windstream.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('suddenlink.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('mediacombb.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('cableone.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('rcn.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('twc.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('juno.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('netzero.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('peoplepc.com');

-- Canadian ISPs
INSERT INTO span_whitelist_domains (domain) VALUES ('shaw.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('telus.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('rogers.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('sympatico.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('videotron.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('bell.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('cogeco.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('eastlink.ca');
INSERT INTO span_whitelist_domains (domain) VALUES ('sasktel.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('mts.net');

-- =====================================================
-- LATIN AMERICAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('prodigy.net.mx');
INSERT INTO span_whitelist_domains (domain) VALUES ('infinitum.com.mx');
INSERT INTO span_whitelist_domains (domain) VALUES ('telmex.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('axtel.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('cablevision.net.mx');
INSERT INTO span_whitelist_domains (domain) VALUES ('speedy.com.ar');
INSERT INTO span_whitelist_domains (domain) VALUES ('fibertel.com.ar');
INSERT INTO span_whitelist_domains (domain) VALUES ('ciudad.com.ar');
INSERT INTO span_whitelist_domains (domain) VALUES ('arnet.com.ar');
INSERT INTO span_whitelist_domains (domain) VALUES ('vtr.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('entelchile.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('une.net.co');
INSERT INTO span_whitelist_domains (domain) VALUES ('cantv.net');

-- =====================================================
-- MIDDLE EAST / AFRICA PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('emirates.net.ae');
INSERT INTO span_whitelist_domains (domain) VALUES ('eim.ae');
INSERT INTO span_whitelist_domains (domain) VALUES ('etisalat.ae');
INSERT INTO span_whitelist_domains (domain) VALUES ('du.ae');
INSERT INTO span_whitelist_domains (domain) VALUES ('saudi.net.sa');
INSERT INTO span_whitelist_domains (domain) VALUES ('link.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('mweb.co.za');
INSERT INTO span_whitelist_domains (domain) VALUES ('telkomsa.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('vodamail.co.za');
INSERT INTO span_whitelist_domains (domain) VALUES ('webmail.co.za');
INSERT INTO span_whitelist_domains (domain) VALUES ('lantic.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('netvigator.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('pccw.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('bezeqint.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('walla.co.il');
INSERT INTO span_whitelist_domains (domain) VALUES ('012.net.il');

-- =====================================================
-- GENERAL PURPOSE / MAIL.COM DOMAINS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('mail.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('email.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('usa.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('europe.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('asia.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('africa.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('india.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('china.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('japan.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('korea.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('brazil.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('mexico.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('london.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('paris.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('berlin.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('rome.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('moscow.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('dublin.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('munich.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('madrid.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('amsterdam.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('toronto.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('writeme.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('consultant.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('contractor.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('accountant.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('engineer.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('doctor.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('lawyer.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('musician.org');
INSERT INTO span_whitelist_domains (domain) VALUES ('photographer.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('graphic-designer.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('techie.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('programmer.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('myself.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('post.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('iname.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('inbox.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('cheerful.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('elvisfan.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('witty.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('dr.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('journalist.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('teacher.com');

-- =====================================================
-- SWISS / AUSTRIAN PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('bluewin.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('sunrise.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('hispeed.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('vtx.ch');
INSERT INTO span_whitelist_domains (domain) VALUES ('aon.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('chello.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('drei.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('a1.net');
INSERT INTO span_whitelist_domains (domain) VALUES ('liwest.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('tele2.at');
INSERT INTO span_whitelist_domains (domain) VALUES ('utanet.at');

-- =====================================================
-- ADDITIONAL TRUSTED PROVIDERS
-- =====================================================

INSERT INTO span_whitelist_domains (domain) VALUES ('gmailbox.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('pobox.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('lavabit.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('bluebottle.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('ovi.com');
INSERT INTO span_whitelist_domains (domain) VALUES ('msn.cn');
