db.createCollection("palabrasIncorrecta",{
	validator:{
		$jsonSchema:{
			bsonType: "object",
			required:["palabra"],
			properties:{
				palabra:{
					bsonType: "array",
					items: {bsonType: "string"}
				}
			}
		}
	}
})