db.createCollection("palabrasClave",{
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
	},
	validationLevel:"strict",
	validationAction:"warn"
})
