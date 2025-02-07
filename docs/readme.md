# 📦 Prepare Extensions for Production  

### ✅ OpenCart v3.x  
- **Files inside a folder** (e.g., `upload/`).  
- **Structure:**  
  ```
  /upload/
    ├── admin/
    ├── catalog/
    ├── system/
    ├── install.xml
  ```
- **ZIP the folder** → Upload via **Extensions > Installer**.  

---

### ✅ OpenCart v4.x  
- **No folder** → All files in **root**.  
- **Must include `install.json`**.  
- **Structure:**  
  ```
  /admin/
  /catalog/
  /system/
  install.json
  ```
- **ZIP everything** → Upload via **Extensions > Installer**.  

---

### 📛 Naming Convention  
- **Format:** `agentfy_oc<OC_VERSION>_v<AGENTFY_VERSION>.ocmod.zip`  
- **Examples:**  
  ✅ `agentfy_oc4_v1.0.2.ocmod.zip`  
  ✅ `agentfy_oc3_v2.1.0.ocmod.zip`