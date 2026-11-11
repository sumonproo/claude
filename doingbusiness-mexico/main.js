// Set the inbox that receives consultation requests before launch.
const CONFIG = { email: "" };

const ZH = {
  "skip": "跳到正文",
  "nav.pathway": "落地流程", "nav.services": "服务", "nav.regions": "区域", "nav.guides": "指南", "nav.offices": "办公室",
  "cta.book": "预约咨询", "cta.how": "了解落地流程", "cta.ask": "咨询您的项目",
  "hero.title": "从深圳到蒙特雷，一支团队贯通太平洋两岸。",
  "hero.lede": "我们帮助中国内地和香港企业在墨西哥落地：选址、注册公司、办理许可和招聘。顾问可使用普通话、粤语、西班牙语和英语沟通。",
  "map.ocean": "太平洋", "map.hk": "香港", "map.sz": "深圳", "map.mty": "蒙特雷", "map.bajio": "巴希奥", "map.cdmx": "墨西哥城",
  "clock.hk": "香港", "clock.cdmx": "墨西哥城", "clock.gap": "时差14小时，我们在您的工作时间回复。",
  "why.title": "为什么现在选择在墨西哥建厂",
  "why.1h": "紧邻美国市场", "why.1p": "从蒙特雷用卡车数小时即可抵达德州，而不是数周海运。",
  "why.2h": "美墨加协定规则", "why.2p": "符合原产地规则的货物可享受进入美国和加拿大的优惠关税。我们帮您据此规划供应链。",
  "why.3h": "可直接租用的工业园", "why.3p": "北部和巴希奥地区的成熟工业园提供厂房、水电和许可，让您更快投产。",
  "path.title": "落地流程", "path.lede": "大多数项目都经历这四个阶段，您可以在任何阶段找我们。",
  "path.1h": "评估", "path.1p": "我们分析您的产品、客户和关税风险，直接告诉您墨西哥是否合适。",
  "path.2h": "选址", "path.2p": "我们筛选州和工业园，安排实地考察，并核查土地产权、电力和供水。",
  "path.3h": "注册公司", "path.3p": "我们设立墨西哥实体，在税务局（SAT）登记，开立银行账户，并申请IMMEX等进口计划。",
  "path.4h": "运营", "path.4p": "开业后，我们协助招聘、薪资、管理人员签证、会计和安全规划。",
  "svc.title": "我们负责的事务", "svc.lede": "一个对接人，背后是墨西哥当地的律师、会计师和选址专家。",
  "svc.1h": "外商投资规则", "svc.1p": "哪些行业开放、哪些需要审批，以及需向外资登记处提交哪些文件。",
  "svc.2h": "公司注册", "svc.2p": "在S.A. de C.V.和S. de R.L.之间选择、公证、税务登记和法定代表人。",
  "svc.3h": "工业用地和厂房", "svc.3p": "工业园筛选、租约谈判、定制厂房项目和土地尽职调查。",
  "svc.4h": "海关和进口计划", "svc.4p": "IMMEX、PROSEC和美墨加原产地规则，确保原材料和出口正确计税。",
  "svc.5h": "招聘和劳工", "svc.5p": "劳动合同、工会关系、薪资，以及从中国派驻员工的工作签证。",
  "svc.6h": "安全和风险", "svc.6p": "针对每个厂址的当地安全简报、出行规范和团队实用指引。",
  "reg.title": "在哪里建厂", "reg.lede": "合适的州取决于您的行业、客户和所需的人才。",
  "reg.h1": "区域", "reg.h2": "主要州", "reg.h3": "常见行业", "reg.h4": "适合您，如果您",
  "reg.r1": "东北部", "reg.r1s": "新莱昂州、科阿韦拉州、塔毛利帕斯州", "reg.r1i": "家电、汽车零部件、钢铁、电子", "reg.r1f": "主要通过卡车发货到美国",
  "reg.r2": "西北部", "reg.r2s": "下加利福尼亚州、索诺拉州、奇瓦瓦州", "reg.r2i": "电子、医疗器械、航空航天", "reg.r2f": "供应美国西海岸",
  "reg.r3": "巴希奥", "reg.r3s": "瓜纳华托州、克雷塔罗州、阿瓜斯卡连特斯州、圣路易斯波托西州", "reg.r3i": "汽车、航空航天、塑料", "reg.r3f": "服务汽车厂商，需要大量劳动力",
  "reg.r4": "中部", "reg.r4s": "墨西哥城、墨西哥州、普埃布拉州", "reg.r4i": "消费品、物流、服务业", "reg.r4f": "主要面向墨西哥本地客户",
  "guide.title": "指南", "guide.lede": "免费、通俗易懂的中英文简报。",
  "guide.t1": "投资", "guide.1": "外商投资规则：哪些开放、限制或保留",
  "guide.t2": "公司设立", "guide.2": "逐步设立墨西哥公司",
  "guide.t3": "选址", "guide.3": "租用工业园厂房：需核查的成本和合同条款",
  "guide.t4": "安全", "guide.4": "墨西哥安全：给来访管理人员的实用简报",
  "off.title": "我们的办公室",
  "off.1": "香港", "off.1p": "客户会议和项目管理",
  "off.2": "中国内地", "off.2p": "初步接洽和市场评估",
  "off.3": "墨西哥城", "off.3p": "法律、税务、实地考察和政府申报",
  "ct.title": "介绍一下您的项目", "ct.lede": "顾问将在一个工作日内用您选择的语言回复。",
  "ct.name": "您的姓名", "ct.company": "公司", "ct.email": "工作邮箱", "ct.stage": "您目前处于哪个阶段？",
  "ct.s1": "仍在考虑墨西哥", "ct.s2": "正在寻找厂址", "ct.s3": "准备注册公司", "ct.s4": "已在运营",
  "ct.msg": "您生产什么产品？客户是谁？", "ct.send": "发送请求",
  "foot.by": "Doing Business Mexico 是 VTZ 旗下平台。", "foot.note": "指南仅供一般参考，不构成法律或税务建议。"
};

const MSG = {
  en: { missing: "Fill in your name, company and a valid work email.", sent: "Your email app is open with the request. Send it from there.", noInbox: "Online requests are not connected yet. Please use the office contacts above." },
  zh: { missing: "请填写姓名、公司和有效的工作邮箱。", sent: "已在您的邮件应用中生成请求，请从那里发送。", noInbox: "在线请求尚未开通，请通过上方办公室联系我们。" }
};

const nodes = [...document.querySelectorAll("[data-i18n]")];
const EN = Object.fromEntries(nodes.map(n => [n.dataset.i18n, n.textContent]));
let lang = "en";

function setLang(next) {
  lang = next === "zh" ? "zh" : "en";
  const dict = lang === "zh" ? ZH : EN;
  nodes.forEach(n => { const v = dict[n.dataset.i18n]; if (v) n.textContent = v; });
  document.documentElement.lang = lang === "zh" ? "zh-Hans" : "en";
  document.querySelectorAll("[data-lang]").forEach(b => b.setAttribute("aria-pressed", String(b.dataset.lang === lang)));
  try { localStorage.setItem("dbm-lang", lang); } catch (e) {}
  tick();
}
document.querySelectorAll("[data-lang]").forEach(b => b.addEventListener("click", () => setLang(b.dataset.lang)));

// Live clocks for Hong Kong and Mexico City
function tick() {
  document.querySelectorAll("[data-tz]").forEach(el => {
    const now = new Date();
    el.textContent = new Intl.DateTimeFormat(lang === "zh" ? "zh-CN" : "en-GB", { hour: "2-digit", minute: "2-digit", timeZone: el.dataset.tz }).format(now);
    el.dateTime = now.toISOString();
  });
}
setInterval(tick, 15000);

let saved = null;
try { saved = localStorage.getItem("dbm-lang"); } catch (e) {}
if (!saved && /^zh/i.test(navigator.language || "")) saved = "zh";
setLang(saved || "en");

// Contact form
const form = document.getElementById("contact-form");
const status = form.querySelector(".form__status");
form.addEventListener("submit", e => {
  e.preventDefault();
  const req = ["f-name", "f-co", "f-email"].map(id => document.getElementById(id));
  let firstBad = null;
  req.forEach(f => {
    const bad = !f.value.trim() || (f.type === "email" && !f.checkValidity());
    f.setAttribute("aria-invalid", String(bad));
    if (bad && !firstBad) firstBad = f;
  });
  status.classList.remove("is-error");
  if (firstBad) { status.textContent = MSG[lang].missing; status.classList.add("is-error"); firstBad.focus(); return; }
  if (!CONFIG.email) { status.textContent = MSG[lang].noInbox; status.classList.add("is-error"); return; }
  const d = new FormData(form);
  const body = `Name: ${d.get("name")}\nCompany: ${d.get("company")}\nEmail: ${d.get("email")}\nStage: ${d.get("stage")}\nLanguage: ${lang}\n\n${d.get("message")}`;
  window.location.href = `mailto:${CONFIG.email}?subject=${encodeURIComponent("Consultation request: " + d.get("company"))}&body=${encodeURIComponent(body)}`;
  status.textContent = MSG[lang].sent;
});
