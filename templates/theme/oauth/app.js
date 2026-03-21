const nexaUI = NexaUI();
  const render = await nexaUI.Storage().api("oauth").config({
    token: true
  });
  const crypto = nexaUI.Crypto("NexaQrV1");
  const Config = crypto.decode(render.token);
  const crud = nexaUI.Firebase("qrlogin", Config);
  // Real-time listener untuk data QR login
  const unsubscribe = crud.red((allData) => {
    // Cari data dengan uniqueId yang sesuai
    const loginData = allData.find(
      (item) => item.key === NEXA.controllers.data.uniqueId
    );

    if (loginData) {
      // User berhasil login melalui QR Code
      if (loginData.success === true) {

        // Set session di web browser dengan data dari API
        fetch(NEXA.url + "/signin", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            email: loginData.email,
            password: loginData.password,
          }),
        })
          .then((response) => {
            // Hapus data dari Firebase setelah login berhasil
            crud.del(NEXA.controllers.data.uniqueId);

            // Stop real-time listener
            unsubscribe();

            // Redirect ke dashboard atau halaman yang sesuai
            setTimeout(() => {
              window.location.href = NEXA.url + "/" + loginData.user_name;
            }, 1000);
          })
          .catch((error) => {
            console.error("Error setting web session:", error);
          });
      }
    }
  });

  // Check existing data (one-time)
  const existingUser = await crud.get(NEXA.controllers.data.uniqueId);
  if (existingUser) {
    console.log("📋 Existing data:", existingUser);
  }

  initQRCode();
  function initQRCode() {
    try {
      // QR Code with logo (auto-generate)
      window.qr5 = new nexaUI.NexaQrcode("qr5", {
        text: NEXA.controllers.data.uniqueId,
        width: 200,
        height: 200,
        logo: NEXA.url + "/assets/images/favicon.png",
        logoSize: 0.25,
        logoMargin: 10,
        logoRadius: 12,
        correctLevel: "H", // High error correction for better logo support
      });
    } catch (error) {
      console.error("Error initializing QR code:", error);
    }
  }