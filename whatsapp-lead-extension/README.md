# RV CRM - WhatsApp Lead Capture Extension

## Ye Kya Hai?

Chrome/Firefox extension jo WhatsApp Web pe **Ctrl+G** press karne pe currently open chat ka phone number automatically CRM me Lead Form me bhar deta hai.

## Kaise Install Karein?

### Chrome:
1. Chrome me jaao: `chrome://extensions/`
2. **Developer mode** ON karo (top right corner me toggle hai)
3. **"Load unpacked"** button pe click karo
4. Is `whatsapp-lead-extension` folder ko select karo
5. Extension install ho jayega! ✅

### Firefox:
1. Firefox me jaao: `about:debugging#/runtime/this-firefox`
2. **"Load Temporary Add-on"** pe click karo
3. Is folder me se `manifest.json` file select karo
4. Extension load ho jayega! ✅

> **Note:** Firefox me temporary add-on browser restart pe hat jayega. Permanent install ke liye extension ko sign karwana padega.

## Kaise Use Karein?

1. **WhatsApp Web** kholo → `https://web.whatsapp.com/`
2. Koi bhi **chat open karo** (jis number ki lead banana hai)
3. **`Ctrl + G`** press karo
4. CRM ka Leads page ek naye tab me khulega — **phone number already filled hoga** aur source "WhatsApp" set hoga
5. Baaki details bharo (Name, Email, City, etc.) aur **Save** karo

## Zaroori Baatein

- **Unsaved numbers:** Agar number saved nahi hai WhatsApp me, to number directly header me dikhta hai — ye sabse reliable hoga.
- **Saved contacts:** Agar contact saved hai, to number name ke peeche chhupa hota hai. Extension try karega dhundhne ka, lekin agar na mile to contact info panel kholke (naam pe click karke) try karo.
- CRM URL: `https://crm.rvallsolutions.com/` (content.js me change kar sakte ho agar URL badal jaye)

## Troubleshooting

| Problem | Solution |
|---------|----------|
| "Phone number nahi mila" | Contact info panel kholo (header pe click karo) aur phir Ctrl+G try karo |
| Extension kaam nahi kar raha | Chrome extensions page pe check karo ki enabled hai |
| CRM pe modal nahi khula | Login check karo CRM me, phir try karo |
