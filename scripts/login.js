sessionStorage.clear();

function server_login(model) {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "POST",
            // Corrected endpoint from login.php to controller_login.php
            url: "php/controlador/controller_login.php",
            data: {
                trama: JSON.stringify(model)
            },
            success: function (response) {
                // The new PHP logic automatically returns a true JS object if the header application/json is set.
                // We check if it is a string just in case, but jQuery usually parses application/json automatically.
                let data = typeof response === "string" ? JSON.parse(response) : response;
                resolve(data);
            },
            error: function (error) {
                console.error("AJAX Error:", error);
                reject(error);
            }
        });
    });
}

function server_email(model) {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "POST",
            url: "php/controlador/email.php",
            data: {
                trama: JSON.stringify(model)
            },
            success: function (response) {
                let data = typeof response === "string" ? JSON.parse(response) : response;
                resolve(data);
            },
            error: function (error) {
                reject(error);
            }
        });
    });
}

async function iniciar_sesion() {
    let userNameInput = $("#lg-nombre").val() || "";
    let userPassInput = $("#lg-contraseña").val() || "";

    let errorMessage = document.getElementById('mensaje-error');

    if (!userNameInput.trim() || !userPassInput.trim()) {
        errorMessage.style.display = 'block';
        errorMessage.textContent = 'Por favor, ingrese usuario y contraseña.';
        return;
    }

    let model = {
        accion: 0,
        nombre: userNameInput.trim(),
        contraseña: userPassInput.trim(), // Keep key mapping consistent with what PHP expects
    };

    try {
        let res = await server_login(model);

        // Checking against the new JSON strict mapping from PHP
        if (res.status === 'error') {
            errorMessage.style.display = 'block';
            errorMessage.textContent = res.message || 'Usuario o contraseña incorrectos. Por favor, inténtelo nuevamente.';
        } else {
            // res.data is expected to be an array [matricula, nombre, correo] 
            // Note: The original PHP code seemed to return a role in index 4, but only requested 3 fields from DB. 
            // We'll map what we have from the new controller_login.php output.
            sessionStorage.setItem("user_data", JSON.stringify(res.data));
            sessionStorage.setItem("log", 'true');

            // Temporary role logic since original plf.sql shows `tipo` field exists but wasn't fully pulled by original logic
            // For now, if "tipo" or role isn't explicitly Admin, redirect to client.
            window.location.href = "vista_cliente.html";
        }
    } catch (e) {
        errorMessage.style.display = 'block';
        errorMessage.textContent = 'Error de conexión con el servidor.';
    }
}

async function recuperar_contraseña() {
    let model = {
        accion: 0,
        correo: $("#rpc-correo").val().trim(),
    };
    alert("Enviando Correo, espere...");

    try {
        let response = await server_email(model);
        let emailmessages = document.getElementById('mensaje-correo-success');
        let emailmessaged = document.getElementById('mensaje-correo-danger');

        if (response.status === 'success' || response.resultado === true) {
            emailmessages.style.display = 'block';
            emailmessages.textContent = 'Te hemos enviado un correo para recuperar tu contraseña.';
            emailmessaged.style.display = 'none';
        } else {
            emailmessaged.style.display = 'block';
            emailmessaged.textContent = 'El correo ingresado no está registrado.';
            emailmessages.style.display = 'none';
        }
    } catch (e) {
        alert("Error de red intentando enviar el correo.");
    }
}
