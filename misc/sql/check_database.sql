-- procedure to check database referential integrity:

-- Table mice:

-- check related litters:
select * from mice where not litter_id is null and not exists (select * from litters x where x.id = litter_id );

-- check related strains:
select * from mice where not strain_id is null and not exists (select * from strains x where x.id = strain_id );

-- check related cages:
select * from mice where not cage_id is null and not exists (select * from cages x where x.id = cage_id );

-- check related users:
select * from mice where not user_id is null and not exists (select * from users x where x.id = user_id );

-- check related protocols:
select * from mice where not protocol_id is null and not exists (select * from protocols x where x.id = protocol_id );

-- check sex
select * from mice where not sex in ('M', 'F');

-- Table litters:

-- check related cages:
select * from litters where not breeding_cage_id is null and not exists (select * from breeding_cages x where x.id = breeding_cage_id );

-- check related users:
select * from litters where not user_id is null and not exists (select * from users x where x.id = user_id );

-- check related strains:
select * from litters x where not strain_id is null and not exists (select * from strains x where x.id = strain_id );

-- check related father:
select * from litters where not father_id is null and not exists (select * from mice x where x.id = father_id );

-- check related mother:
select * from litters where not mother_id is null and not exists (select * from mice x where x.id = mother_id );

-- check related mother2:
select * from litters where not mother2_id is null and not exists (select * from mice x where x.id = mother2_id );

-- check related mother3:
select * from litters where not mother3_id is null and not exists (select * from mice x where x.id = mother3_id );

-- Table strains:

-- check related users:
select * from strains where not user_id is null and not exists (select * from users x where x.id = user_id );

-- check related assigned users:
select * from strains where not assigned_user_id is null and not exists (select * from users x where x.id = assigned_user_id );

-- Table cages:

-- check related breeding cages:
select * from cages where cagetype = 'breeding' and not exists (select * from breeding_cages x where x.id = cages.id);

-- check related weaning cages:
select * from cages where cagetype = 'weaning' and not exists (select * from weaning_cages x where x.id = cages.id);

-- check related users:
select * from cages where not user_id is null and not exists (select * from users x where x.id = user_id );

-- check related protocols:
select * from cages where not protocol_id is null and not exists (select * from protocols x where x.id = protocol_id );

-- Table breeding_cages

-- check related cages:
select * from breeding_cages where not exists (select * from cages x where cagetype = 'breeding' and x.id = breeding_cages.id);

-- check related assigned stud:
select * from breeding_cages where not assigned_stud_id is null and not exists (select * from mice x where x.id = assigned_stud_id );

-- Table weaning_cages

-- check related cages:
select * from weaning_cages where not exists (select * from cages x where cagetype = 'weaning' and x.id = weaning_cages.id);

-- check related litters:
select * from weaning_cages where not litter_id is null and not exists (select * from litters x where x.id = litter_id );

-- check sex
select * from weaning_cages where not sex in ('M', 'F');

-- Table transfers

-- check related cages:
select * from transfers where not from_cage_id is null and not exists (select * from cages x where x.id = from_cage_id );
select * from transfers where not to_cage_id is null and not exists (select * from cages x where x.id = to_cage_id );

-- check related mice:
select * from transfers where not mouse_id is null and not exists (select * from mice x where x.id = mouse_id );

-- check related users:
select * from transfers where not user_id is null and not exists (select * from users x where x.id = user_id );

-- check if any records have null for both origin and destination
select * from transfers where to_cage_id is null and from_cage_id is null;

-- Table searches

-- check related users:
select * from searches where not exists (select * from users x where x.id = user_id );

-- Table user_prefs

-- check related users:
select * from user_prefs where not exists (select * from users x where x.id = user_id );
