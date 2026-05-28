const path = window.location.pathname;

const parts = path.split('/').filter(Boolean);

if (parts.length === 0) {
    console.log("home");
	document.getElementById("home").classList.add("active");
} else {
    const folder = parts[parts.length - 2];
    const file = parts[parts.length - 1];

	//controllo se sono alla home
	if(folder === undefined && file == "index.php"){
		document.getElementById("home").classList.add("active");
		ImpostaPercorsi();
	}
	else if(folder == "Progetti"){
		document.getElementById("progetti").classList.add("active");
		ImpostaPercorsi();
		
	} else if(folder == "AmbitiOrientati" && file == "ambiti.php"){
		document.getElementById("ambiti").classList.add("active");
		ImpostaPercorsi();
		
	} else if(folder == "AmbitiOrientati" && file == "orientati.php"){
		document.getElementById("orientati").classList.add("active");
		ImpostaPercorsi();
		
	} else if(folder == "Eventi"){
		document.getElementById("eventi").classList.add("active");
		ImpostaPercorsi();
	} else if(folder == "LinkUtili"){
		document.getElementById("linkutili").classList.add("active");
		ImpostaPercorsi();
	}
    console.log(folder);
    console.log(file);
	
	function ImpostaPercorsi(){
		let home = document.getElementById("home");
		let progetti = document.getElementById("progetti");
		let ambiti = document.getElementById("ambiti");
		let orientati = document.getElementById("orientati");
		let linkUtili = document.getElementById("linkutili");
		let logo = document.getElementById("logo");
		
		home.href="../index.php";
		progetti.href="../Progetti/index.php";
		ambiti.href="../AmbitiOrientati/ambiti.php";
		orientati.href="../AmbitiOrientati/orientati.php";
		linkUtili.href="../LinkUtili/index.php";
		logo.src ="../pictures/logo.png";
		
	}
}